<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Plugin\TelegramNotifier\Telegram;
use Dotclear\Plugin\TelegramNotifier\TelegramAction;

/**
 * @brief       FrontendSession plugin TelegramNotifier behaviors.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class PluginTelegramBehaviors
{
    /**
     * Add telegram action for new frontend registration.
     */
    public static function TelegramNotifierAddActions(Telegram $telegram): void
    {
        $telegram->addActions([
            // On frontend user registration
            new TelegramAction(
                id: My::id() . 'AfterSignup',
                type: 'message',
                name: __('New frontend registration'),
                description: __('Send message on new user frontend registration'),
                permissions: App::auth()->makePermissions([App::auth()::PERMISSION_ADMIN])
            ),
        ]);
    }

    /**
     * Telegram notification.
     */
    public static function FrontendSessionAfterSignup(Cursor $cur): void
    {
        if (!App::plugins()->moduleExists('TelegramNotifier')) {
            return;
        }

        $message = sprintf('*%s*', __('New user registration')) . "\n" .
            "-- \n" .
            sprintf(__('*Blog:* [%s](%s)'), App::blog()->name(), App::blog()->url()) . "\n" .
            sprintf(__('*User:* %s'), $cur->getField('user_id')) . "\n" .
            sprintf(__('*Email:* %s'), $cur->getField('user_email')) . "\n" .
            "-- \n" .
            __('Follow this link below to validate it:') . "\n" .
            // manual admin URL as we are in Frontend
            App::config()->adminUrl() . '?process=User&id=' . $cur->getField('user_id');

        $telegram = new Telegram();
        $telegram
            ->setAction(My::id() . 'AfterSignup')
            ->setContent($message)
            ->setFormat('markdown')
            ->send();
    }
}
