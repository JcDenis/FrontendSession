<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Database\Statement\SelectStatement;
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
    public string $state   = My::STATE_DISCONNECTED;
    public string $data    = '';
    public string $success = '';

    private readonly SessionHandler $session;
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

                    if (!App::auth()->userID() || ($this->uid() !== $_SESSION[My::id() . '_browser_uid'])) {
                        $welcome = false;
                    }
                }

                if (!$welcome) {
                    $this->session()->destroy();
                    // Avoid loop caused by old cookie
                    $p    = $this->session()->getCookieParameters(false, -600);
                    $p[3] = '/';
                    $p[4] = static::domain();
                    setcookie(...$p);   // @phpstan-ignore-line
                }
            } catch (Throwable) {
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
        if (!isset($_SESSION[My::id() . '_user_id']) && isset($_COOKIE[My::id()]) && strlen((string) $_COOKIE[My::id()]) == 104) {
            // If we have a cookie, go through auth process with user_key
            $user_id = substr((string) $_COOKIE[My::id()], 40);
            $user_id = @unpack('a32', @pack('H*', $user_id));
            if (is_array($user_id)) {
                $user_id  = trim((string) $user_id[1]);
                $user_key = substr((string) $_COOKIE[My::id()], 0, 40);
            } else {
                $user_id = $user_key = null;
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
        $bits = parse_url((string) App::blog()->url());

        return empty($bits['scheme']) || !preg_match('%^http[s]?$%', $bits['scheme']) ? false : $bits['scheme'] === 'https';
    }

    /**
     * Cookie domain.
     *
     * User session can be share between subdomain of a multiblog.
     */
    public static function domain(): string
    {
        return defined('FRONTENDSESSION_COOKIE_DOMAIN') ? FRONTENDSESSION_COOKIE_DOMAIN : '';
    }

    /**
     * Get browser UID.
     */
    private function uid(string $user_id = ''): string
    {
        return $user_id === '' ? Http::browserUID(App::config()->masterKey()) :
            Http::browserUID(
                App::config()->masterKey() .
                $user_id .
                App::auth()->cryptLegacy($user_id)
            ) . bin2hex(pack('a32', $user_id));
    }

    /**
     * Remove cookie.
     */
    private function reset(): void
    {
        if (isset($_COOKIE[My::id()])) {
            unset($_COOKIE[My::id()]);
            setcookie(My::id(), '', ['expires' => time() - 3600, 'path' => '/', 'domain' => static::domain(), 'secure' => $this->ssl()]);
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
    public function redirect(string ...$args): void
    {
        App::blog()->triggerBlog(); // force no cache
        Http::redirect($args === [] ? Http::getSelfURI() : implode('/', $args));
    }

    /**
     * Kill public session.
     */
    public function kill(): void
    {
        // Ping user's blogs
        $this->triggerBlogs();

        // Kill session
        $this->session()->destroy();

        // Unset cookie if necessary
        $this->reset();
    }

    /**
     * Ping others user blogs on signin/signout.
     *
     * This reduces to near zero cache and is time consuming.
     */
    private function triggerBlogs(): void
    {
        if (App::auth()->userID() != '' && static::domain() !== '') {
            $old_blog = App::blog()->id();
            if (App::auth()->isSuperAdmin()) {
                // taken from App::users()->updUser()
                $sql = new SelectStatement();
                $rs  = $sql
                    ->distinct()
                    ->column('blog_id')
                    ->from(App::con()->prefix() . App::blog()::POST_TABLE_NAME)
                    ->where('user_id = ' . $sql->quote((string) App::auth()->userID()))
                    ->select();

                if (!is_null($rs)) {
                    while ($rs->fetch()) {
                        App::blog()->loadFromBlog($rs->f('blog_id'));
                        App::blog()->triggerBlog();
                    }
                }
            } else {
                $res = App::users()->getUserPermissions((string) App::auth()->userID());
                foreach ($res as $blog_id => $perms) {
                    if (isset($perms['p'][My::id()]) || isset($perms['p'][App::auth()::PERMISSION_ADMIN])) {
                        App::blog()->loadFromBlog($blog_id);
                        App::blog()->triggerBlog();
                    }
                }
            }
            App::blog()->loadFromBlog($old_blog);
        }
    }

    /**
     * Decode password change data.
     *
     * @return  array<string, string|bool>
     */
    public function decode(string $data): array
    {
        $data    = explode('/', $data);
        $user    = base64_decode($data[0] ?: '', true);
        $cookie  = $data[1] ?? '';
        $user_id = '';

        if ($user !== false && strlen($cookie) == 104) {
            $user_id = @unpack('a32', @pack('H*', substr($cookie, 40)));
            if (is_array($user_id)) {
                $user_id = App::auth()->checkUser(trim($user), null, substr($cookie, 0, 40)) ? trim($user) : '';
            } else {
                $user_id = trim((string) $user_id);
            }
        }

        return [
            'user_id'  => $user_id,
            'remember' => ($data[2] ?? 0) === '1',
        ];
    }

    /**
     * Encode password change data.
     *
     * @param   array<int, string|bool>     $data
     */
    public function encode(array $data, bool $encode = false): string
    {
        if (count($data) == 2 && $encode) {
            $data = [
                base64_encode((string) $data[0]),
                $this->uid((string) $data[0]),
                (string) !empty($data[2]),
            ];
        }

        return implode('/', $data);
    }

    /**
     * Check if user has rights.
     */
    public function check(?string $user_id, ?string $user_pwd = null, ?string $user_key = null, ?string $redir = null, bool $remember = false): bool
    {
        if ($user_id === null || is_string($user_pwd) && $user_pwd === '' || is_string($user_key) && $user_key === '') {
            return false;
        }

        if (App::auth()->checkUser($user_id, $user_pwd, $user_key, false) === true
         && App::auth()->check(My::id(), App::blog()->id())               === true
        ) {
            // check if user is pending activation
            if ((int) App::auth()->getInfo('user_status') == My::USER_PENDING) {
                $this->reset();
                $this->redirect(App::blog()->url() . App::url()->getURLFor(My::id()), My::ACTION_SIGNIN, My::STATE_PENDING);
                // check if user is not enabled
            } elseif (App::status()->user()->isRestricted((int) App::auth()->getInfo('user_status'))) {
                $this->reset();
                $this->redirect(App::blog()->url() . App::url()->getURLFor(My::id()), My::ACTION_SIGNIN, My::STATE_DISABLED);
                // check if user must change password
            } elseif (App::auth()->mustChangePassword()) {
                $this->reset();
                $this->redirect(App::blog()->url() . App::url()->getURLFor(My::id()), My::ACTION_CHANGE, $this->encode([$user_id, $remember], true));
            } else {
                $this->start();
                $_SESSION[My::id() . '_user_id']     = $user_id;
                $_SESSION[My::id() . '_browser_uid'] = $this->uid();
                $_SESSION[My::id() . '_blog_id']     = App::blog()->id();

                if ($remember) {
                    setcookie(
                        My::id(),
                        $user_key === null ? $this->uid($user_id) : $_COOKIE[My::id()],
                        ['expires' => strtotime('+15 days'), 'path' => '/', 'domain' => static::domain(), 'secure' => $this->ssl()]
                    );

                    $this->triggerBlogs();
                }

                return true;
            }
        } else {
            $this->reset();
        }

        return false;
    }
}
