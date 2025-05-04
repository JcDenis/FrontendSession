<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Database\Session as DatabaseSession;
use Throwable;

/**
 * @brief       FrontendSession module session handler.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class SessionHandler extends DatabaseSession
{
    public function __construct(string $session_name, bool $ssl)
    {
        parent::__construct(
            con: App::con(),
            table : App::con()->prefix() . self::SESSION_TABLE_NAME,
            cookie_name: $session_name,
            cookie_secure: $ssl,
            cookie_domain: FrontendSession::domain(),
            ttl: App::config()->sessionTtl()
        );

        register_shutdown_function(function (): void {
            try {
                if (session_id()) {
                    // Explicitly close session before DB connection
                    session_write_close();
                }
                App::con()->close();
            } catch (Throwable) {
                // Ignore exceptions
            }
        });
    }
}
