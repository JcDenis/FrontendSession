<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\Mail\Mail;
use Dotclear\Interface\Core\BlogInterface;
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

    public const ACTION_SIGNIN  = 'signin';
    public const ACTION_SIGNOUT = 'signout';
    public const ACTION_SIGNUP  = 'signup';
    public const ACTION_PENDING = 'pending';

    /**
     * Send mail.
     */
    public static function mailSender(string $dest, string $subject, string $message): void
    {
        if (!self::settings()->get('email_from')) {
            return;
        }

        $headers = [
            'From: ' . sprintf('%1$s <' . (string) self::settings()->get('email_from') . '>', Mail::B64Header(App::blog()->name())),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8;',
            'Content-Transfer-Encoding: 8bit',
            'X-Originating-IP: ' . Http::realIP(),
            'X-Mailer: Dotclear',
            'X-Blog-Id: ' . Mail::B64Header(App::blog()->id()),
            'X-Blog-Name: ' . Mail::B64Header(App::blog()->name()),
            'X-Blog-Url: ' . Mail::B64Header(App::blog()->url()),
        ];

        $subject = Mail::B64Header(sprintf('[%s] %s', App::blog()->name(), $subject));

        Mail::sendMail($dest, $subject, $message, $headers);
    }
}
