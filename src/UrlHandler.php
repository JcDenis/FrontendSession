<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;

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
     * Session login endpoint.
     * 
     * User sign in, sign up, sign out.
     */
    public static function sessionSign(?string $args): void
    {
        if (!My::settings()->get('active')) {
            self::p404();
        }

        $action = '';

        // action from URL
        if (!is_null($args)) {
            $args = substr($args, 1);
            $args = explode('/', $args);
            $action = $args[0];
        }

        // action from POST form
        if (!empty($_POST[My::id() . 'action'])) {
            $action = $_POST[My::id() . 'action'];
        }

        App::frontend()->context()->session_state   = App::auth()->userID() == '' ? My::SESSION_DISCONNECTED : My::SESSION_CONNECTED;
        App::frontend()->context()->session_message = My::settings()->get(App::auth()->userID() == '' ? My::SESSION_DISCONNECTED : My::SESSION_CONNECTED);
        App::frontend()->context()->form_error      = null;

        switch ($action) {
            case My::ACTION_SIGNOUT:
                Session::killSession();
                Session::redirect(App::blog()->url());
                break;

            case My::ACTION_SIGNIN:
                Session::checkUser(
                    $_POST[My::id() . 'login'] ?? null,
                    $_POST[My::id() . 'password'] ?? null,
                    null,
                    $_REQUEST[My::id() . 'redir'] ?? null,
                    !empty($_POST[My::id() . 'remember'])
                );
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
                                throw new Exception('Failed to add user');
                            }
                            App::auth()->sudo([App::users(), 'setUserPermissions'], $r_user_id, [App::blog()->id() => [My::id() => true]]);
                            // @todo    email notification on user registration
                            App::frontend()->context()->form_error = __('Thank you for your registration. An administrator will validate your request soon.');
                            
                            // send confirmation email
                            self::sendRegistrationMail($r_user_id, $r_user_pwd, $r_user_email);
                        } catch (Exception) {
                            $err[] = __('Something went wrong while trying to register user.');
                        }
                    }

                    if (count($err)) { // @phpstan-ignore-line
                        App::frontend()->context()->form_error = implode(" \n", $err);
                    }
                }
                self::serveTemplate(My::id() . '.html');
                break;

            case My::ACTION_PENDING:
                if (App::auth()->userID() == '') {
                    App::frontend()->context()->form_error      = __('Account is not yet activated.');
                    App::frontend()->context()->session_state   = My::SESSION_PENDING;
                    App::frontend()->context()->session_message = My::settings()->get(My::SESSION_PENDING);
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

        $default_template = Path::real(App::plugins()->moduleInfo(My::id(), 'root')) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
        if (is_dir($default_template . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
        }

        self::serveDocument($tpl);
    }

    /**
     * Send registration email.
     */
    private static function sendRegistrationMail(string $user_id, string $user_pwd, string $user_email): void
    {
        // user email
        My::mailSender(
            $user_email,
            __('Confirmation of registration'),
            wordwrap(
                sprintf(__('Thank you for your registration on blog %s!'), App::blog()->id()) . "\n\n" .
                sprintf(__('Your login is: %s'), $user_id) . "\n" .
                sprintf(__('Your password is: %s'), $user_pwd) . "\n\n" .
                __('Administrators need to review before activate your account but they will do it as soon as possible.') . "\n" .
                __('You will receive an email when it will be ready.') . "\n",
                80
            )
        );

        // admin email
        foreach(explode(',', (string) My::settings()->get('email_registration')) as $mail) {
            if (!empty(trim($mail))) {
                My::mailSender(
                    trim($mail),
                    __('New user registration'),
                    wordwrap(
                        sprintf(__('A new user registration has been made on blog %s!'), App::blog()->id()) . "\n\n" .
                        sprintf(__('User login is: %s'), $user_id) . "\n" .
                        sprintf(__('User email is: %s'), $user_email) . "\n" .
                        __('Administrators need to review user account and activate it.') . "\n" .
                        App::config()->adminUrl() . '?' . http_build_query([
                                'status' => My::USER_PENDING,
                                'q' => $user_id,
                                'switchblog' => App::blog()->id()
                            ]) . "\n",
                        80
                    )
                );
            }
        }
    }
}
