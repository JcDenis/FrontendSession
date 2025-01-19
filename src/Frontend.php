<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\L10n;
use Dotclear\Exception\PreconditionException;

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
        l10n::set(dirname(__DIR__) . '/locales/' . App::lang()->getLang() . '/public');

        // template values and block
        App::frontend()->template()->addBlock('FrontendSessionIf', [FrontendTemplate::class, 'FrontendSessionIf']);
        App::frontend()->template()->addValue('FrontendSessionNonce', [FrontendTemplate::class, 'FrontendSessionNonce']);
        App::frontend()->template()->addValue('FrontendSessionID', [FrontendTemplate::class, 'FrontendSessionID']);
        App::frontend()->template()->addValue('FrontendSessionUrl', [FrontendTemplate::class, 'FrontendSessionUrl']);
        App::frontend()->template()->addValue('FrontendSessionMessage', [FrontendTemplate::class, 'FrontendSessionMessage']);
        App::frontend()->template()->addValue('FrontendSessionDisplayName', [FrontendTemplate::class, 'FrontendSessionDisplayName']);
        App::frontend()->template()->addValue('FrontendSessionData', [FrontendTemplate::class, 'FrontendSessionData']);

        // behaviors
        App::behavior()->addBehaviors([
            // public widgets
            'initWidgets'       => [Widgets::class, 'initWidgets'],
            'publicHeadContent' => function (): void {
                echo My::cssLoad('frontendsession-dotty');
            },
        ]);

        // Check session
        Session::startSession();

        // Check cookie
        Session::checkCookie();

        // Check nonce from POST requests
        if ($_POST !== [] && (empty($_POST[My::id() . 'xd_check']) || !App::nonce()->checkNonce($_POST[My::id() . 'xd_check']))) {
            throw new PreconditionException();
        }

        return true;
    }
}
