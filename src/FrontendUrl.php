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
class FrontendUrl extends Url
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

        // Parse request
        $args   = explode('/', (string) $args);
        $action = $_POST[My::id() . 'action'] ?? ($args[0] ?? '');
        $state  = $_POST[My::id() . 'state'] ?? ($args[1] ?? '');
        $redir  = $_REQUEST[My::id() . 'redir'] ?? null;

        // Set user state
        App::frontend()->context()->session_state = App::auth()->userID() == '' ? My::STATE_DISCONNECTED : My::STATE_CONNECTED;

        // Do action
        switch ($action) {
            case My::ACTION_SIGNOUT:
                App::frontend()->context()->frontend_session->kill();
                App::frontend()->context()->frontend_session->redirect(App::blog()->url());
                break;

            case My::ACTION_SIGNIN:
                $signin_login    = $_POST[My::id() . 'signin_login'] ?? '';
                $signin_password = $_POST[My::id() . 'signin_password'] ?? '';
                $signin_remember = !empty($_POST[My::id() . 'signin_remember']);

                if (App::auth()->userID() == '' && in_array($state, [My::STATE_PENDING, My::STATE_DISABLED])) {
                    self::$form_error[] = $state == My::STATE_DISABLED ? __('This account is disabled.') : __('Your account is not yet activated. An administrator will review your account and validate it soon.');
                    self::serveTemplate(My::id() . '.html');
                } else {
                    App::frontend()->context()->frontend_session->check(
                        $signin_login,
                        $signin_password,
                        null,
                        $redir,
                        $signin_remember
                    );
                }
                App::frontend()->context()->frontend_session->redirect($redir);
                break;

            case My::ACTION_SIGNUP:
                $signup_login     = $_POST[My::id() . 'signup_login'] ?? '';
                $signup_firstname = $_POST[My::id() . 'signup_firstname'] ?? '';
                $signup_name      = $_POST[My::id() . 'signup_name'] ?? '';
                $signup_email     = $_POST[My::id() . 'signup_email'] ?? '';
                $signup_vemail    = $_POST[My::id() . 'signup_vemail'] ?? '';
                $signup_password  = $_POST[My::id() . 'signup_password'] ?? '';
                $signup_vpassword = $_POST[My::id() . 'signup_vpassword'] ?? '';

                if (!empty($signup_login)) {
                    $err = [];

                    if (!preg_match('/^[A-Za-z0-9._-]{3,}$/', $signup_login)) {
                        $err[] = __('This username is not valid.');
                    } elseif (App::users()->userExists($signup_login)) {
                        $err[] = __('This username is not available.');
                    }

                    if ($signup_email != $signup_vemail) {
                        $err[] = __('Emails missmatch.');
                    } elseif (!Text::isEmail($signup_email)) {
                        $err[] = __('Email is not valid.');
                    }

                    if ($signup_password != $signup_vpassword) {
                        $err[] = __('Passwords missmatch.');
                    } elseif (strlen($signup_password) < 6) {
                        $err[] = __('Password must be at lesat 6 characters long.');
                    }

                    if (!count($err)) {
                        try {
                            $cur = App::auth()->openUserCursor();
                            $cur->user_id        = $signup_login;
                            $cur->user_name      = $signup_name;
                            $cur->user_firstname = $signup_firstname;
                            $cur->user_email     = $signup_email;
                            $cur->user_pwd       = $signup_password;
                            $cur->user_status    = My::USER_PENDING;
                            $cur->user_lang      = (string) App::blog()->settings()->system->lang;

                            if ($signup_login != App::auth()->sudo([App::users(), 'addUser'], $cur)) {
                                self::$form_error[] = __('Something went wrong while trying to register user.');
                            } else {
                                App::auth()->sudo([App::users(), 'setUserPermissions'], $signup_login, [App::blog()->id() => [My::id() => true]]);
                                self::$form_error[] = __('Thank you for your registration. An administrator will validate your request soon.');
                                
                                // send confirmation email
                                Mail::sendRegistrationMail($signup_login, $signup_password, $signup_email);
                            }
                        } catch (Throwable) {
                            self::$form_error[] = __('Something went wrong while trying to register user.');
                        }
                    }
                }
                self::serveTemplate(My::id() . '.html');
                break;

            case My::ACTION_RECOVER:
                $recover_login = $_POST[My::id() . 'recover_login'] ?? '';
                $recover_email = $_POST[My::id() . 'recover_email'] ?? '';

                if (My::settings()->get('enable_recovery')) {
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
                    } elseif (App::auth()->userID() == '' && !empty($recover_login) && !empty($recover_email)) {
                        // check if user is (super)admin
                        $rs = App::users()->getUser($recover_login);
                        if (!$rs->isEmpty() && $rs->admin() != '') {
                            self::$form_error[] = __('You are an admin, you must change password from backend.');
                        } else {
                            try {
                                $recover_key = App::auth()->setRecoverKey($recover_login, $recover_email);
                                Mail::sendRecoveryMail($recover_login, $recover_key, $recover_email);
                                self::$form_error[] = sprintf(__('The e-mail was sent successfully to %s.'), $recover_email);
                            } catch(Throwable) {
                                self::$form_error[] = __('Unknow username or email.');
                            }
                        }
                    }
                }
                self::serveTemplate(My::id() . '.html');
                break;

            case My::ACTION_PASSWORD:
                $change_data      = $_POST[My::id() . 'change_data'] ?? '';
                $change_password  = $_POST[My::id() . 'change_password'] ?? '';
                $change_vpassword = $_POST[My::id() . 'change_vpassword'];

                if (My::settings()->get('enable_recovery')) {
                    // set data for post from
                    if (count($args) == 4 && empty($change_data)) {
                        self::$form_error[] = __('You must set a new password.');
                        App::frontend()->context()->session_state = My::STATE_PASSWORD;
                        App::frontend()->context()->session_data  = $args[1] . '/' . $args[2] . '/' . $args[3];
                    } elseif (!empty($change_data)) {
                        App::frontend()->context()->session_state = My::STATE_PASSWORD;
                        App::frontend()->context()->session_data  = $change_data;

                        // decode data
                        $data     = explode('/', $change_data);
                        $user     = base64_decode($data[0] ?? '', true);
                        $cookie   = $data[1] ?? '';
                        $remember = ($args[2] ?? 0) === '1';
                        $check    = false;
                        $user_id  = '';

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
                        } elseif (empty($change_password) || $change_password != $change_vpassword) {
                            self::$form_error[] = __("Passwords don't match");
                        } elseif (App::auth()->checkUser($user_id, $change_password)) {
                            self::$form_error[] = __("You didn't change your password.");
                        } else {
                            // change user password
                            try {
                                $cur                  = App::auth()->openUserCursor();
                                $cur->user_change_pwd = 0;
                                $cur->user_pwd        = $change_password;
                                App::users()->updUser($user_id, $cur);

                                // sign in user
                                App::frontend()->context()->frontend_session->check($user_id, $change_password, null, null, $remember);
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
