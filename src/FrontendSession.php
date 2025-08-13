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
    /**
     * Errors stack.
     *
     * @var     array<int, string>  $errors
     */
    private array $errors = [];

    public string $state   = My::STATE_DISCONNECTED;
    public string $data    = '';
    public string $success = '';

    public function __construct(
    ) {
        App::frontend()->session()->start();

        if (isset($_SESSION[My::id() . '_user_id'])) {
            // Check here for user and IP address
            $this->check($_SESSION[My::id() . '_user_id']);

            if ($this->uid() !== $_SESSION[My::id() . '_browser_uid']) {
                App::frontend()->session()->destroy();
                $this->setCookie();
                $this->redirect(App::blog()->url());
            }
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
     * Set cookie.
     *
     * This takes default cookie parameters and complete them.
     *
     * @param   int     $expires    The expires delay
     * @param   string  $value      The cookie content
     */
    private function setCookie(int $expires = -600, string $value = ''): void
    {
        $p = App::frontend()->session()->getCookieParameters(false, $expires);
        $p[0] = My::id();
        $p[1] = $value;

        setcookie(...$p);
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
            $this->setCookie();
        }
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
        // Kill session
        App::frontend()->session()->destroy();

        // Unset cookie if necessary
        $this->reset();
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
                $_SESSION[My::id() . '_user_id']     = $user_id;
                $_SESSION[My::id() . '_browser_uid'] = $this->uid();
                $_SESSION[My::id() . '_blog_id']     = App::blog()->id();

                if ($remember) {
                    $this->setCookie(
                        strtotime('+15 days'),
                        $user_key === null ? $this->uid($user_id) : $_COOKIE[My::id()],
                    );
                }

                return true;
            }
        } else {
            $this->reset();
        }

        return false;
    }

    /**
     * Get errors messages.
     *
     * @return   array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if there are errors.
     *
     * @return  bool
     */
    public function hasError(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Add an error message.
     *
     * @param   string  $error  The error message
     */
    public function addError(string $error): void
    {
        $this->errors[] = $error;
    }
}
