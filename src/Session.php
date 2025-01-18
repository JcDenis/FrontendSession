<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Helper\Network\Http;
use Throwable;

/**
 * @brief       FrontendSession module session helper.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Session
{
    /**
     * Start using session.
     */
	public static function startSession(): bool
	{
        // HTTP/1.1
        //header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        //header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

        if (App::auth()->sessionExists()) {
            // If we have a session we launch it now
            try {
                if (!App::auth()->checkSession()) {
                    // Avoid loop caused by old cookie
                    $p    = App::session()->getCookieParameters(false, -600);
                    $p[3] = '/';
                    setcookie(...$p);   // @phpstan-ignore-line
                    
                    // Should use redirection to logout user
                    self::redirect(App::blog()->url());
                }
            } catch (Throwable) {
                //throw new SessionException(__('There seems to be no Session table in your database. Is Dotclear completly installed?'));
            }

            // Check blog to use and log out if no result
            if (!isset($_SESSION['sess_blog_id'])) {
                $_SESSION['sess_blog_id'] = App::blog()->id();
            } elseif ($_SESSION['sess_blog_id'] != App::blog()->id()) {
                unset($_SESSION['sess_blog_id']);
            }

	        // Check user right on blog
	        if (!isset($_SESSION['sess_blog_id']) || !App::auth()->check(My::id(), App::blog()->id())) {
	            // Kill public session
	            self::killSession();
	            // Should use redirection to logout user
                self::redirect(App::blog()->url());
	        }
        }

        return true;
	}

    /**
     * Check if user has cookie.
     */
    public static function checkCookie(): void
    {        
        if (!isset($_SESSION['sess_user_id']) && isset($_COOKIE[My::id()]) && strlen($_COOKIE[My::id()]) == 104) {

            // If we have a cookie, go through auth process with user_key
            $user_id = substr($_COOKIE[My::id()], 40);
            $user_id = @unpack('a32', @pack('H*', $user_id));
            if (is_array($user_id)) {
                $user_id  = trim($user_id[1]);
                $user_key = substr($_COOKIE[My::id()], 0, 40);
                $user_pwd = null;
            } else {
                $user_id = $user_pwd = $user_key = null;
            }

            Session::checkUser($user_id, null, $user_key, $_REQUEST[My::id() . 'redir'] ?? null, true);
        }
    }

    /**
     * Check if user has rights.
     */
	public static function checkUser(?string $user_id, ?string $user_pwd, ?string $user_key, ?string $redir, bool $remember = false): void
	{
        if ($user_id !== null && ($user_pwd !== null || $user_key !== null)) {
            // we check the user and its perm
            if (App::auth()->checkUser($user_id, $user_pwd, $user_key, false) === true
             && App::auth()->check(My::id(), App::blog()->id()) === true
            ) {
                // check if user is pending activation
                if ((int) App::auth()->getInfo('user_status') == My::USER_PENDING) {
                    self::resetCookie();
                    self::redirect(App::blog()->url() . App::url()->getURLFor(My::id()) . '/' . My::ACTION_PENDING);
                // check if user is not enabled
                } elseif (App::status()->user()->isRestricted((int) App::auth()->getInfo('user_status'))) {
                    self::resetCookie();
                } else {
                    App::session()->start();
                    $_SESSION['sess_user_id']     = $user_id;
                    $_SESSION['sess_browser_uid'] = Http::browserUID(App::config()->masterKey());
                    $_SESSION['sess_blog_id']     = App::blog()->id();

                    if ($remember) {
                        if ($user_key === null) {
                            $cookie = Http::browserUID(
                                App::config()->masterKey() .
                                $user_id .
                                App::auth()->cryptLegacy($user_id)
                            ) . bin2hex(pack('a32', $user_id));
                        } else {
                            $cookie = $_COOKIE[My::id()];
                        }

                        setcookie(
                            My::id(),
                            $cookie,
                            ['expires' => strtotime('+15 days'), 'path' => '/', 'domain' => '', 'secure' => self::isSSL()]
                        );
                    }
                }
            } else {
                self::resetCookie();
            }

            // Must redirect for changes to take effect
            self::redirect($redir ?? Http::getSelfURI());
        }
    }

    /**
     * Kill session helper.
     */
    public static function killSession(): void
    {
        // Kill session
        App::session()->destroy();

        // Unset cookie if necessary
        self::resetCookie();
    }

    /**
     * Remove cookie.
     */
    public static function resetCookie(): void
    {
        if (isset($_COOKIE[My::id()])) {
            unset($_COOKIE[My::id()]);
            setcookie(My::id(), '', time() - 3600, '', '', self::isSSL());
        }
    }

    /**
     * Check if blog use SSL.
     */
    public static function isSSL(): bool
    {
        $bits = parse_url(App::blog()->url());

        if (empty($bits['scheme']) || !preg_match('%^http[s]?$%', $bits['scheme'])) {
            return false;
        }

        return $bits['scheme'] == 'https';
    }

    /**
     * Redirection and cache cleaning on user state change.
     *
     * Using session on frontend reduce to zero cache system.
     */
    public static function redirect(string $redir): void
    {
        App::blog()->triggerBLog(); // force no cache
        Http::redirect($redir);
    }
}