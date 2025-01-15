<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Dotclear\Exception\PreconditionException;
use Exception;

/**
 * @brief       FrontendSession module frontend process.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !My::settings()->get('active')) {
            return false;
        }

        // locales in public file
        l10n::set(dirname(__DIR__) . '/locales/' . App::lang()->getLang() . '/public');

        // template values and block
        App::frontend()->template()->addBlock('FrontendSessionIf', [FrontendTemplate::class, 'FrontendSessionIf']);
        App::frontend()->template()->addValue('FrontendSessionNonce', [FrontendTemplate::class, 'FrontendSessionNonce']);
        App::frontend()->template()->addValue('FrontendSessionID', [FrontendTemplate::class, 'FrontendSessionID']);
        App::frontend()->template()->addValue('FrontendSessionUrl', [FrontendTemplate::class, 'FrontendSessionUrl']);
        App::frontend()->template()->addValue('FrontendSessionConnected', [FrontendTemplate::class, 'FrontendSessionConnected']);
        App::frontend()->template()->addValue('FrontendSessionDisconnected', [FrontendTemplate::class, 'FrontendSessionDisconnected']);
        App::frontend()->template()->addValue('FrontendSessionDisplayName', [FrontendTemplate::class, 'FrontendSessionDisplayName']);

        // behaviors
        App::behavior()->addBehaviors([
            // public widgets
            'initWidgets'       => [Widgets::class, 'initWidgets'],
            'publicHeadContent' => function (): void {
                echo My::cssLoad('frontendsession-dotty');
            },
        ]);

        self::doAuthControl();

        return true;
    }

    /**
     * Chek user rights and cookies.
     */
    private static function doAuthControl(): void
    {
        if (!My::settings()->get('active')) {
            return;
        }

        App::frontend()->context()->form_error = $user_id = $user_pwd = $user_key = null;

        // HTTP/1.1
        //header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        //header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

        // if we have a POST signout information, go throug logout process
        if (!empty($_POST[My::id() . '_action']) && $_POST[My::id() . '_action'] == 'signout') {
            App::blog()->triggerBlog();
            self::resetCookie();
            Http::redirect(App::blog()->url());

        // if we have POST signup information, go throug auth process
        }elseif (!empty($_POST[My::id() . '_user_login'])) {
            $err = [];
            $r_user_id = $r_user_firstname = $r_user_name = $r_user_email = $r_user_pwd = '';

            // Check nonce from POST requests
            if ($_POST !== [] && (empty($_POST['xd_check']) || !App::nonce()->checkNonce($_POST['xd_check']))) {
                throw new PreconditionException();
            }

            if (!preg_match('/^[A-Za-z0-9._-]{3,}$/', $_POST[My::id() . '_user_login'])) {
                $err[] = __('This username is not valid.');
            } elseif (App::users()->userExists($_POST[My::id() . '_user_login'])) {
                $err[] = __('This username is not available.');
            } else {
                $r_user_id = $_POST[My::id() . '_user_login'];
            }

            $r_user_firstname = $_POST[My::id() . '_user_firstname'] ?? '';
            $r_user_name = $_POST[My::id() . '_user_name'] ?? '';

            if (($_POST[My::id() . '_user_email'] ?? '') != ($_POST[My::id() . '_user_esecond'] ?? '')) {
                $err[] = __('Emails missmatch.');
            } elseif (!Text::isEmail($_POST[My::id() . '_user_email'] ?? '')) {
                $err[] = __('Email is not valid.');
            } else {
                $r_user_email = $_POST[My::id() . '_user_email'];
            }

            if (($_POST[My::id() . '_user_pwd'] ?? '') != ($_POST[My::id() . '_user_psecond'] ?? '')) {
                $err[] = __('Passwords missmatch.');
            } elseif (strlen($_POST[My::id() . '_user_pwd'] ?? '') < 6) {
                $err[] = __('Password must be at lesat 6 characters long.');
            } else {
                $r_user_pwd = $_POST[My::id() . '_user_pwd'];
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
                    $err[] = __('Thank you for your registration. An administrator will validate your request soon.');
                } catch (Exception) {
                    $err[] = __('Something went wrong while trying to register user.');
                }
            }

            if (count($err)) { // @phpstan-ignore-line
                App::frontend()->context()->form_error = implode(" \n", $err);
            }

        // if we have POST signin information, go throug auth process
        } elseif (!empty($_POST[My::id() . '_login']) && !empty($_POST[My::id() . '_password'])) {
            $user_id  = $_POST[My::id() . '_login'];
            $user_pwd = $_POST[My::id() . '_password'];

        // if we have COOKIE information, go throug auth process
        } elseif (isset($_COOKIE[My::id()]) && strlen($_COOKIE[My::id()]) == 104) {
            # If we have a cookie, go through auth process with user_key
            $user_id = substr($_COOKIE[My::id()], 40);
            $user_id = @unpack('a32', @pack('H*', $user_id));
            if (is_array($user_id)) {
                $user_id  = trim($user_id[1]);
                $user_key = substr($_COOKIE[My::id()], 0, 40);
                $user_pwd = null;
            } else {
                $user_id = null;
            }

        // no COOKIE nor POST signin and password information
        } elseif (!empty($_POST[My::id() . '_login']) || !empty($_POST[My::id() . '_password'])) {
            App::frontend()->context()->form_error = __("Error: your password may be wrong or you haven't an account or you haven't ask for its activation.");
        }

        if ($user_id !== null && ($user_pwd !== null || $user_key !== null)) {
            // we check the user and its perm
            if (App::auth()->checkUser($user_id, $user_pwd, $user_key, false) === true
             && App::auth()->check(My::id(), App::blog()->id()) === true
             //&& !App::status()->user()->isRestricted((int) App::auth()->getInfo('user_status'))
            ) {
                // check if user is pending activation
                if ((int) App::auth()->getInfo('user_status') == My::USER_PENDING) {
                    self::resetCookie();
                    Http::redirect(App::blog()->url() . App::url()->getURLFor(My::id()) . '/pending');
                // check if user is not enabled
                } elseif (App::status()->user()->isRestricted((int) App::auth()->getInfo('user_status'))) {
                     self::resetCookie();
                    Http::redirect(Http::getSelfURI());
                } else {
                    if ($user_key === null) {
                        $cookie_console = Http::browserUID(
                            App::config()->masterKey() .
                            $user_id .
                            App::auth()->cryptLegacy($user_id)
                        ) . bin2hex(pack('a32', $user_id));
                    } else {
                        $cookie_console = $_COOKIE[My::id()];
                    }
                    setcookie(My::id(), $cookie_console, strtotime('+20 hours'), '/', '', self::useSSL());
                }
            } else {
                self::resetCookie();
                // need to replay doAuthControl() to remove user information from Auth if it exists but have no permissions
                Http::redirect(Http::getSelfURI());
            }
        }
    }

    /**
     * Remove cookie
     */
    public static function resetCookie(): void
    {
        if (isset($_COOKIE[My::id()])) {
            unset($_COOKIE[My::id()]);
            setcookie(My::id(), '', time() - 3600, '/', '', self::useSSL());
        }
    }

    /**
     * Check SSL.
     */
    public static function useSSL(): bool
    {
        $bits = parse_url(App::blog()->url());

        if (empty($bits['scheme']) || !preg_match('%^http[s]?$%', $bits['scheme'])) {
            return false;
        }

        return $bits['scheme'] == 'https';
    }
}
