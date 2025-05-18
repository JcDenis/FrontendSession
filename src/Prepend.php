<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Stack\Status;

/**
 * @brief       FrontendSession module prepend.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Add frontend permission (required to login in frontend)
        App::auth()->setPermissionType(
            My::id(),
            My::name()
        );

        // Add session login URL
        App::url()->register(
            My::id(),
            'session',
            '^session(/.+)?$',
            FrontendUrl::sessionAction(...)
        );

        // Add user status
        App::status()->user()->set((new Status(
            My::USER_PENDING,
            My::id(),
            'Pending registration',
            'pending registration (>1)',
            My::fileURL('icon.svg')
        )));

        // Plugin telegram notification
        App::behavior()->addBehaviors([
            'TelegramNotifierAddActions' => PluginTelegramBehaviors::TelegramNotifierAddActions(...),
        ]);

        return true;
    }
}
