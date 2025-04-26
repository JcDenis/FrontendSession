<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\L10n;

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
        //L10n::set(dirname(__DIR__) . '/locales/' . App::lang()->getLang() . '/public');

        // template values and block
        App::frontend()->template()->addBlock('FrontendSessionIf', FrontendTemplate::FrontendSessionIf(...));
        App::frontend()->template()->addValue('FrontendSessionUrl', FrontendTemplate::FrontendSessionUrl(...));
        App::frontend()->template()->addValue('FrontendSessionNonce', FrontendTemplate::FrontendSessionNonce(...));
        App::frontend()->template()->addValue('FrontendSessionInfo', FrontendTemplate::FrontendSessionInfo(...));
        App::frontend()->template()->addValue('FrontendSessionContent', FrontendTemplate::FrontendSessionContent(...));
        App::frontend()->template()->addValue('FrontendSessionSuccess', FrontendTemplate::FrontendSessionSuccess(...));
        App::frontend()->template()->addValue('FrontendSessionUser', FrontendTemplate::FrontendSessionUser(...));

        // behaviors
        App::behavior()->addBehaviors([
            'initWidgets'                    => Widgets::initWidgets(...),
            'coreBlogGetPosts'               => FrontendBehaviors::coreBlogGetPosts(...),
            'coreBeforeCommentCreate'        => FrontendBehaviors::coreBeforeCommentCreate(...),
            'publicHeadContent'              => FrontendBehaviors::publicHeadContent(...),
            'publicCommentFormBeforeContent' => FrontendBehaviors::publicCommentFormBeforeContent(...),
            'FrontendSessionAfterSignup'     => FrontendBehaviors::FrontendSessionAfterSignup(...),
            'publicCommentFormBeforeContent' => FrontendBehaviors::publicCommentFormBeforeContent(...),
        ]);

        App::frontend()->context()->frontend_session = new FrontendSession(My::SESSION_NAME);

        return true;
    }
}
