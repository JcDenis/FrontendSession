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
    public const SESSION_NAME = 'daxd';

    public const USER_PENDING = -201;

    public const STATE_CONNECTED    = 'connected';
    public const STATE_DISCONNECTED = 'disconnected';
    public const STATE_PENDING      = 'pending';
    public const STATE_DISABLED     = 'disabled';
    public const STATE_CHANGE       = 'change';

    public const ACTION_SIGNIN   = 'signin';
    public const ACTION_SIGNOUT  = 'signout';
    public const ACTION_SIGNUP   = 'signup';
    public const ACTION_RECOVER  = 'recover';
    public const ACTION_CHANGE   = 'change';
}
