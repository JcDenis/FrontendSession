<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief       FrontendSession module definition.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class My extends MyPlugin
{
    public const USER_PENDING = -201;

    public const STATE_CONNECTED = 'connected';

    public const STATE_DISCONNECTED = 'disconnected';

    public const STATE_PENDING = 'pending';

    public const STATE_DISABLED = 'disabled';

    public const STATE_CHANGE = 'change';

    public const ACTION_SIGNIN = 'signin';

    public const ACTION_SIGNOUT = 'signout';

    public const ACTION_SIGNUP = 'signup';

    public const ACTION_RECOVER = 'recover';

    public const ACTION_CHANGE = 'change';

    public const ACTION_UPDPASS = 'updpass';

    /**
     * Get the URL of a status icon.
     *
     * A status is declared on every page load, but backend URLs do not exist
     * in some backend contexts (installation, upgrade, command line): no icon there.
     *
     * @param   string  $resource   The icon path in module
     */
    public static function statusIcon(string $resource): string
    {
        if (App::task()->checkContext('BACKEND') && !App::backend()->url()->dumpUrls()->offsetExists('load.plugin.file')) {
            return '';
        }

        return self::fileURL($resource);
    }
}
