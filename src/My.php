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

    public const SESSION_CONNECTED    = 'connected';
    public const SESSION_DISCONNECTED = 'disconnected';
    public const SESSION_PENDING      = 'pending';
    public const SESSION_PASSWORD     = 'newpwd';
    public const SESSION_DISABLED     = 'disabled';

    public const ACTION_SIGNIN   = 'signin';
    public const ACTION_SIGNOUT  = 'signout';
    public const ACTION_SIGNUP   = 'signup';
    public const ACTION_PENDING  = 'pending';
    public const ACTION_RECOVER  = 'recover';
    public const ACTION_PASSWORD = 'newpwd';
}
