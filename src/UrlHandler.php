<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Text;
use Throwable;

/**
 * @brief       FrontendSession module URL handler.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class UrlHandler extends Url
{
    /**
     * Form errors.
     *
     * @var     array<int, string>  $form_error
     */
    private static array $form_error = [];

    /**
     * Session login endpoint.
     * 
     * User sign in, sign up, sign out.
     */
    public static function sessionSign(?string $args): void
    {
        if (!My::settings()->get('active')) {
            self::p404();
        }

        $action = $state = '';

        // action from URL
        if (!is_null($args)) {
            $args = substr($args, 1);
            $args = explode('/', $args);
            $action = $args[0];
            $state = $args[1] ?? '';
        }

        // action from POST form
        if (!empty($_POST[My::id() . 'action'])) {
            $action = $_POST[My::id() . 'action'];
        }
        if (!empty($_POST[My::id() . 'state'])) {
            $state = $_POST[My::id() . 'state'];
        }

        App::frontend()->context()->session_state = App::auth()->userID() == '' ? My::STATE_DISCONNECTED : My::STATE_CONNECTED;

        switch ($action) {
            case My::ACTION_SIGNOUT:
                App::frontend()->context()->frontend_session->kill();
                App::frontend()->context()->frontend_session->redirect(App::blog()->url());
                break;

            case My::ACTION_SIGNIN:
                if (App::auth()->userID() == '' && in_array($state, [My::STATE_PENDING, My::STATE_DISABLED])) {
                    self::$form_error[] = $state == My::STATE_DISABLED ? __('This account is disabled.') : __('Your account is not yet activated. An administrator will review your account and validate it soon.');
                    self::serveTemplate(My::id() . '.html');
                } else {
                    App::frontend()->context()->frontend_session->check(
                        $_POST[My::id() . 'login'] ?? '',
                        $_POST[My::id() . 'password'] ?? '',
                        null,
                        $_REQUEST[My::id() . 'redir'] ?? null,
                        !empty($_POST[My::id() . 'remember'])
                    );
                }
                App::frontend()->context()->frontend_session->redirect($_POST[My::id() . 'redir'] ?? null);
                break;

            case My::ACTION_SIGNUP:
                if (!empty($_POST[My::id() . 'user_login'])) {
                    $err = [];
                    $r_user_id = $r_user_firstname = $r_user_name = $r_user_email = $r_user_pwd = '';

                    if (!preg_match('/^[A-Za-z0-9._-]{3,}$/', $_POST[My::id() . 'user_login'])) {
                        $err[] = __('This username is not valid.');
                    } elseif (App::users()->userExists($_POST[My::id() . 'user_login'])) {
                        $err[] = __('This username is not available.');
                    } else {
                        $r_user_id = $_POST[My::id() . 'user_login'];
                    }

                    $r_user_firstname = $_POST[My::id() . 'user_firstname'] ?? '';
                    $r_user_name = $_POST[My::id() . 'user_name'] ?? '';

                    if (($_POST[My::id() . 'user_email'] ?? '') != ($_POST[My::id() . 'user_esecond'] ?? '')) {
                        $err[] = __('Emails missmatch.');
                    } elseif (!Text::isEmail($_POST[My::id() . 'user_email'] ?? '')) {
                        $err[] = __('Email is not valid.');
                    } else {
                        $r_user_email = $_POST[My::id() . 'user_email'];
                    }

                    if (($_POST[My::id() . 'user_pwd'] ?? '') != ($_POST[My::id() . 'user_psecond'] ?? '')) {
                        $err[] = __('Passwords missmatch.');
                    } elseif (strlen($_POST[My::id() . 'user_pwd'] ?? '') < 6) {
                        $err[] = __('Password must be at lesat 6 characters long.');
                    } else {
                        $r_user_pwd = $_POST[My::id() . 'user_pwd'];
                    }

                    if (!count($err)) {
                        try {
                            $cur = App::auth()->openUserCursor();
                            $cur->user_id        = $r_user_id;
                            $cur->user_name      = $r_user_name;
                            $cur->user_firstname = $r_user_firstname;
                            $cur->user_email     = $r_user_email;
                            $cur->user_pwd       = $r_user_pwd;
                            $cur->user_status    = My::USER_PENDING;
                            $cur->user_lang      = (string) App::blog()->settings()->system->lang;

                            if ($r_user_id != App::auth()->sudo([App::users(), 'addUser'], $cur)) {
                                self::$form_error[] = __('Something went wrong while trying to register user.');
                            } else {
                                App::auth()->sudo([App::users(), 'setUserPermissions'], $r_user_id, [App::blog()->id() => [My::id() => true]]);
                                self::$form_error[] = __('Thank you for your registration. An administrator will validate your request soon.');
                                
                                // send confirmation email
                                Mail::sendRegistrationMail($r_user_id, $r_user_pwd, $r_user_email);
                            }
                        } catch (Throwable) {
                            self::$form_error[] = __('Something went wrong while trying to register user.');
                        }
                    }
                }
                self::serveTemplate(My::id() . '.html');
                break;

            case My::ACTION_RECOVER:
                if (My::settings()->get('enable_recovery')) {
                    $user_id    = $_POST[My::id() . 'recover_login'] ?? '';
                    $user_email = $_POST[My::id() . 'recover_email'] ?? '';

                    // change password from recovery email
                    if (!empty($state)) {
                        try {
                            $res = App::auth()->recoverUserPassword($state);
                            Mail::sendPasswordMail($res['user_id'], $res['new_pass'], $res['user_email']);
                            self::$form_error[] = __('Your new password is in your mailbox.');
                        } catch(Throwable) {
                            self::$form_error[] = __('Unknow username or email.');
                        }
                    // send recovery email
                    } elseif (App::auth()->userID() == '' && !empty($user_id) && !empty($user_email)) {
                        // check if user is (super)admin
                        $rs = App::users()->getUser($user_id);
                        if (!$rs->isEmpty() && $rs->admin() != '') {
                            self::$form_error[] = __('You are an admin, you must change password from backend.');
                        } else {
                            try {
                                $user_key = App::auth()->setRecoverKey($user_id, $user_email);
                                Mail::sendRecoveryMail($user_id, $user_key, $user_email);
                                self::$form_error[] = sprintf(__('The e-mail was sent successfully to %s.'), $user_email);
                            } catch(Throwable) {
                                self::$form_error[] = __('Unknow username or email.');
                            }
                        }
                    }
                }
                self::serveTemplate(My::id() . '.html');
                break;

            case My::ACTION_PASSWORD:
                if (My::settings()->get('enable_recovery')) {
                    // set data for post from
                    if (!is_null($args) && count($args) == 4 && empty($_POST[My::id() . 'data'])) {
                        self::$form_error[] = __('You must set a new password.');
                        App::frontend()->context()->session_state = My::STATE_PASSWORD;
                        App::frontend()->context()->session_data  = $args[1] . '/' . $args[2] . '/' . $args[3];
                    } elseif (!empty($_POST[My::id() . 'data'])) {
                        App::frontend()->context()->session_state = My::STATE_PASSWORD;
                        App::frontend()->context()->session_data  = $_POST[My::id() . 'data'];

                        // decode data
                        $data     = explode('/', $_POST[My::id() . 'data']);
                        $user     = base64_decode($data[0] ?? '', true);
                        $cookie   = $data[1] ?? '';
                        $remember = ($args[2] ?? 0) === '1';
                        $check    = false;
                        $user_id  = '';
                        $user_pwd = $_POST[My::id() . 'newpwd_pwd'] ?? '';

                        if ($user !== false && strlen($cookie) == 104) {
                            $user_id = substr($cookie, 40);
                            $user_id = @unpack('a32', @pack('H*', $user_id));
                            if (is_array($user_id)) {
                                $user_id  = trim($user);
                                $user_key = substr($cookie, 0, 40);
                                $check    = App::auth()->checkUser($user_id, null, $user_key);
                            } else {
                                $user_id = trim((string) $user_id);
                            }
                        }

                        // check if user is (super)admin 
                        $rs = App::users()->getUser($user_id);
                        if (!$rs->isEmpty() && $rs->admin() != '') {
                            self::$form_error[] = __('You are an admin, you must change password from backend.');
                        } elseif (!$check) {
                            self::$form_error[] = __("Unable to retrieve user informations.");
                        } elseif (empty($user_pwd) || $user_pwd != $_POST[My::id() . 'newpwd_psecond']) {
                            self::$form_error[] = __("Passwords don't match");
                        } elseif (App::auth()->checkUser($user_id, $user_pwd)) {
                            self::$form_error[] = __("You didn't change your password.");
                        } else {
                            // change user password
                            try {
                                $cur                  = App::auth()->openUserCursor();
                                $cur->user_change_pwd = 0;
                                $cur->user_pwd        = $user_pwd;
                                App::users()->updUser($user_id, $cur);

                                // sign in user
                                App::frontend()->context()->frontend_session->check($user_id, $user_pwd, null, null, $remember);
                            } catch (Throwable $e) {
                                self::$form_error[] = $e->getMessage();
                            }
                        }
                    }
                }
                self::serveTemplate(My::id() . '.html');
                break;

            default:
                self::serveTemplate(My::id() . '.html');
                break;

        }
    }

    /**
     * Serve template.
     */
    private static function serveTemplate(string $tpl): void
    {
        // use only dotty tplset
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
        if ($tplset != 'dotty') {
            self::p404();
        }

        if (count(self::$form_error) > 0) {
            App::frontend()->context()->form_error = implode("\n", self::$form_error);
        }

        $default_template = Path::real(App::plugins()->moduleInfo(My::id(), 'root')) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
        if (is_dir($default_template . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
        }

        self::serveDocument($tpl);
    }
}
