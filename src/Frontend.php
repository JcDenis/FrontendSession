<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       FrontendSession module frontend process.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status() || !My::settings()->get('active')) {
            return false;
        }

        App::frontend()->template()->addBlocks([
            'FrontendSessionIf' => FrontendTemplate::FrontendSessionIf(...),
        ]);
        App::frontend()->template()->addValues([
            'FrontendSessionUrl'     => FrontendTemplate::FrontendSessionUrl(...),
            'FrontendSessionNonce'   => FrontendTemplate::FrontendSessionNonce(...),
            'FrontendSessionInfo'    => FrontendTemplate::FrontendSessionInfo(...),
            'FrontendSessionContent' => FrontendTemplate::FrontendSessionContent(...),
            'FrontendSessionSuccess' => FrontendTemplate::FrontendSessionSuccess(...),
            'FrontendSessionUser'    => FrontendTemplate::FrontendSessionUser(...),
        ]);

        App::behavior()->addBehaviors([
            'initWidgets'                    => Widgets::initWidgets(...),
            'coreBlogGetPosts'               => FrontendBehaviors::coreBlogGetPosts(...),
            'publicBeforeCommentCreate'      => FrontendBehaviors::publicBeforeCommentCreate(...),
            'publicHeadContent'              => FrontendBehaviors::publicHeadContent(...),
            'publicEntryAfterContent'        => FrontendBehaviors::publicEntryAfterContent(...),
            'publicCommentAfterContent'      => FrontendBehaviors::publicCommentAfterContent(...),
            'publicCommentFormBeforeContent' => FrontendBehaviors::publicCommentFormBeforeContent(...),
            'FrontendSessionAfterSignup'     => PluginTelegramBehaviors::FrontendSessionAfterSignup(...),
        ]);

        App::frontend()->context()->frontend_session = new FrontendSession();

        return true;
    }
}
