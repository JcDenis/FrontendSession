<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\Cursor;
use Dotclear\Helper\L10n;
use Dotclear\Exception\PreconditionException;
use Dotclear\Plugin\TelegramNotifier\Telegram;

/**
 * @brief       FrontendSession module frontend process.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !My::settings()->get('active')) {
            return false;
        }

        // locales in public file
        L10n::set(dirname(__DIR__) . '/locales/' . App::lang()->getLang() . '/public');

        // template values and block
        App::frontend()->template()->addBlock('FrontendSessionIf', FrontendTemplate::FrontendSessionIf(...));
        App::frontend()->template()->addValue('FrontendSessionNonce', FrontendTemplate::FrontendSessionNonce(...));
        App::frontend()->template()->addValue('FrontendSessionID', FrontendTemplate::FrontendSessionID(...));
        App::frontend()->template()->addValue('FrontendSessionUrl', FrontendTemplate::FrontendSessionUrl(...));
        App::frontend()->template()->addValue('FrontendSessionMessage', FrontendTemplate::FrontendSessionMessage(...));
        App::frontend()->template()->addValue('FrontendSessionDisplayName', FrontendTemplate::FrontendSessionDisplayName(...));
        App::frontend()->template()->addValue('FrontendSessionData', FrontendTemplate::FrontendSessionData(...));

        // behaviors
        App::behavior()->addBehaviors([
            // public widgets
            'initWidgets'       => Widgets::initWidgets(...),
            'publicHeadContent' => function (): void {
                echo My::cssLoad('frontendsession-dotty');
            },
            // telegram notification
            My::id() . 'AfterSignup' => function (Cursor $cur): void {
                if (!App::plugins()->moduleExists('TelegramNotifier')) {
                    return;
                }

                $message = 
                    sprintf('*%s*', __('New user registration')) . "\n" .
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
            },
        ]);

        App::frontend()->context()->frontend_session = new FrontendSession(My::SESSION_NAME);

        // Check nonce from POST requests
        if (!empty($_POST[My::id() . 'check']) && !App::nonce()->checkNonce($_POST[My::id() . 'check'])) {
            throw new PreconditionException();
        }

        return true;
    }
}
