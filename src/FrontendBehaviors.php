<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Plugin\TelegramNotifier\Telegram;

/**
 * @brief       FrontendSession frontend behaviors.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendBehaviors
{
            // telegram notification
    public static function FrontendSessionAfterSignup(Cursor $cur): void
    {
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
    }

    /**
     * Public CSS.
     */
    public static function publicHeadContent(): void
    {
        if (!My::settings()->get('disable_css')) {
            echo My::cssLoad('frontendsession-dotty');
        }
    }

    /**
     * Comment form auto completion.
     */
    public static function publicCommentFormBeforeContent(): void
    {
        if (App::auth()->check(My::id(), App::blog()->id())
            && App::frontend()->context()->comment_preview['content'] == ''
        ) {
            App::frontend()->context()->comment_preview['name'] = App::auth()->getInfo('user_cn');
            App::frontend()->context()->comment_preview['mail'] = App::auth()->getInfo('user_email');
            App::frontend()->context()->comment_preview['site'] = App::auth()->getInfo('user_url');
        }
    }
}
