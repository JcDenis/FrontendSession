<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Core\Process;
use Exception;

/**
 * @brief       FrontendSession install class.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            My::settings()->put('active', false, 'boolean', 'Enable public sessions', false, true);
            My::settings()->put('active_registration', false, 'boolean', 'Activate registration form on frontend', false, true);
            My::settings()->put('email_registration', '', 'text', 'Email to send registration confirmation to', false, true);
            My::settings()->put('email_from', '', 'text', 'No-reply email address for confirmation mail', false, true);
            My::settings()->put(My::SESSION_CONNECTED, "You're now connected to the blog.", 'text', 'Connected display text', false, true);
            My::settings()->put(My::SESSION_DISCONNECTED, "You're must be connected to unlock all blog's features.", 'text', 'Diconnected display text', false, true);
            My::settings()->put(My::SESSION_PENDING, "Your account is not yet activated. An administrator will review your account and validate it soon.", 'text', 'User pending display text', false, true);

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
