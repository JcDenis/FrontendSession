<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Exception\SessionException;
use Dotclear\Helper\Network\Http;
use Throwable;

/**
 * @brief       FrontendSession module session helper.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendSession
{
	private SessionHandler $session;
	private bool $session_started = false;

	public function __construct(
		protected string $session_name
	) {
        $this->session = new SessionHandler($this->session_name, $this->ssl());

        // Check SESSION
        if (isset($_COOKIE[$this->session_name])) {
            // If we have a session we launch it now
            try {
		        $welcome = true;
		        $this->start();

		        if (!isset($_SESSION[My::id() . '_user_id'])) {
		            // If session does not exist, logout.
		            $welcome = false;
		        } else {
		            // Check here for user and IP address
		            $this->check($_SESSION[My::id() . '_user_id']);
		            $uid = Http::browserUID(App::config()->masterKey());

		            if (!App::auth()->userID() || ($uid !== $_SESSION[My::id() . '_browser_uid'])) {
		                $welcome = false;
		            }
		        }

		        if (!$welcome) {
		            $this->session()->destroy();
                    // Avoid loop caused by old cookie
                    $p    = $this->session()->getCookieParameters(false, -600);
                    $p[3] = '/';
                    setcookie(...$p);   // @phpstan-ignore-line
                }
            } catch (Throwable $e) {
                throw new SessionException(__('There seems to be no Session table in your database. Is Dotclear completly installed?'));
            }

            // Check blog to use and log out if no result
            if (!isset($_SESSION[My::id() . '_blog_id'])) {
                $_SESSION[My::id() . '_blog_id'] = App::blog()->id();
            } elseif ($_SESSION[My::id() . '_blog_id'] != App::blog()->id()) {
                unset($_SESSION[My::id() . '_blog_id']);
            }

	        // Check user right on blog
	        if (isset($_SESSION[My::id() . '_user_id'])
	        && (!isset($_SESSION[My::id() . '_blog_id']) || !App::auth()->check(My::id(), App::blog()->id()))
	        ) {
	            // Kill public session
	            $this->kill();
	            // Should use redirection to logout user
                $this->redirect(App::blog()->url());
	        }
        }

        // Check COOKIE
        if (!isset($_SESSION[My::id() . '_user_id']) && isset($_COOKIE[My::id()]) && strlen($_COOKIE[My::id()]) == 104) {

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

            $this->check($user_id, null, $user_key, $_REQUEST[My::id() . 'redir'] ?? null, true);
        }
    }

    /**
     * Start (once) session.
     */
    private function start(): void
    {
    	if (!$this->session_started) {
			$this->session()->start();
			$this->session_started = true;
    	}
    }

    /**
     * Check if blog use SSL.
     */
    private function ssl(): bool
    {
        $bits = parse_url(App::blog()->url());

        if (empty($bits['scheme']) || !preg_match('%^http[s]?$%', $bits['scheme'])) {
            return false;
        }

        return $bits['scheme'] == 'https';
    }

    /**
     * Remove cookie.
     */
    private function reset(): void
    {
        if (isset($_COOKIE[My::id()])) {
            unset($_COOKIE[My::id()]);
            setcookie(My::id(), '', time() - 3600, '', '', $this->ssl());
        }
    }

    /**
     * Get session handler.
     */
	public function session(): SessionHandler
	{
		return $this->session;
	}

    /**
     * Redirection and cache cleaning on user state change.
     *
     * Using session on frontend reduce to zero cache system.
     */
    public function redirect(?string $redir = null): void
    {
        App::blog()->triggerBLog(); // force no cache
        Http::redirect($redir ?? Http::getSelfURI());
    }

    /**
     * Kill public session.
     */
    public function kill(): void
    {
        // Kill session
        $this->session()->destroy();

        // Unset cookie if necessary
        $this->reset();
    }

    /**
     * Check if user has rights.
     */
	public function check(?string $user_id, ?string $user_pwd = null, ?string $user_key = null, ?string $redir = null, bool $remember = false): void
	{
        if ($user_id === null || is_string($user_pwd) && empty($user_pwd) || is_string($user_key) && empty($user_key)) {
        	return;
        }

        if (App::auth()->checkUser($user_id, $user_pwd, $user_key, false) === true
         && App::auth()->check(My::id(), App::blog()->id()) === true
        ) {
            // check if user is pending activation
            if ((int) App::auth()->getInfo('user_status') == My::USER_PENDING) {
                $this->reset();
                $this->redirect(App::blog()->url() . App::url()->getURLFor(My::id()) . '/' . My::ACTION_SIGNIN . '/' . My::STATE_PENDING);
            // check if user is not enabled
            } elseif (App::status()->user()->isRestricted((int) App::auth()->getInfo('user_status'))) {
                $this->reset();
                $this->redirect(App::blog()->url() . App::url()->getURLFor(My::id()) . '/' . My::ACTION_SIGNIN . '/' . My::STATE_DISABLED);
            // check if user must change password
            } elseif (App::auth()->mustChangePassword()) {
                $data = implode('/', [
                    base64_encode($user_id),
                    Http::browserUID(
                        App::config()->masterKey() . 
                        $user_id . 
                        App::auth()->cryptLegacy($user_id)
                    ) . bin2hex(pack('a32', $user_id)),
                    $remember ? '0' : '1',
            ]);
                $this->reset();
                $this->redirect(App::blog()->url() . App::url()->getURLFor(My::id()) . '/' . My::ACTION_PASSWORD . '/' . urlencode($data));
            } else {
		        $this->start();
                $_SESSION[My::id() . '_user_id']     = $user_id;
                $_SESSION[My::id() . '_browser_uid'] = Http::browserUID(App::config()->masterKey());
                $_SESSION[My::id() . '_blog_id']     = App::blog()->id();

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
                        ['expires' => strtotime('+15 days'), 'path' => '/', 'domain' => '', 'secure' => $this->ssl()]
                    );
                }
            }
        } else {
            $this->reset();
        }

        // Must redirect for changes to take effect
        //$this->redirect($redir ?? Http::getSelfURI());
    }
}