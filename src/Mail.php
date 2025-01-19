<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\Mail\Mail as RootMail;
use Dotclear\Helper\Text;
use Exception;

/**
 * @brief       FrontendSession module mail helper.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Mail
{
    /**
     * Send mail.
     */
    private static function mailSender(string $dest, string $subject, string $message): void
    {
        if (!My::settings()->get('email_from')) {
            return;
        }

        $headers = [
            'From: ' . sprintf('%1$s <' . (string) My::settings()->get('email_from') . '>', RootMail::B64Header(App::blog()->name())),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8;',
            'Content-Transfer-Encoding: 8bit',
            'X-Originating-IP: ' . Http::realIP(),
            'X-Mailer: Dotclear',
            'X-Blog-Id: ' . RootMail::B64Header(App::blog()->id()),
            'X-Blog-Name: ' . RootMail::B64Header(App::blog()->name()),
            'X-Blog-Url: ' . RootMail::B64Header(App::blog()->url()),
        ];

        $subject = RootMail::B64Header(sprintf('[%s] %s', App::blog()->name(), $subject));

        RootMail::sendMail($dest, $subject, $message, $headers);
    }

    /**
     * Send activation mail.
     */
    public static function sendActivationMail(string $user_email): void
    {
        // user email
        self::mailSender(
            $user_email,
            __('Confirmation of activation'),
            wordwrap(
                sprintf(__('Thank you for your registration on blog "$s"!'), App::blog()->name()) . "\n\n" .
                __('Your account is now activated.') . "\n" .
                sprintf(__('You can now sign in to: %s'), App::blog()->url() . App::url()->getURLFor(My::id())) . "\n",
                80
            )
        );
    }

    /**
     * Send registration email.
     */
    public static function sendRegistrationMail(string $user_id, string $user_pwd, string $user_email): void
    {
        // user email
        self::mailSender(
            $user_email,
            __('Confirmation of registration'),
            wordwrap(
                sprintf(__('Thank you for your registration on blog "%s"!'), App::blog()->name()) . "\n\n" .
                __('Site:') . ' ' . App::blog()->name() . ' - ' . App::blog()->url() . "\n" .
                __('Username:') . ' ' . $user_id . "\n" .
                __('Password:') . ' ' . $user_pwd . "\n\n" .
                __('Administrators need to review before activate your account but they will do it as soon as possible.') . "\n" .
                __('You will receive an email when it will be ready.') . "\n",
                80
            )
        );

        // admin email
        foreach(explode(',', (string) My::settings()->get('email_registration')) as $mail) {
            if (!empty(trim($mail))) {
                self::mailSender(
                    trim($mail),
                    __('New user registration'),
                    wordwrap(
                        sprintf(__('A new user registration has been made on blog "%s" (%s)!'), App::blog()->name(), App::blog()->id()) . "\n\n" .
                        __('Username:') . ' ' . $user_id . "\n" .
                        __('Email:') . ' ' . $user_email . "\n" .
                        __('Administrators need to review user account and activate it.') . "\n" .
                        App::config()->adminUrl() . '?' . http_build_query([
                            'process' => 'Users',
                            'status' => My::USER_PENDING,
                            'q' => $user_id,
                            'switchblog' => App::blog()->id()
                        ]) . "\n",
                        80
                    )
                );
            }
        }
    }

    /**
     * Send recovery key email.
     */
    public static function sendRecoveryMail(string $user_id, string $user_key, string $user_email): void
    {
        self::mailSender(
            $user_email,
            __('Password reset'),
            wordwrap(
                __('Someone has requested to reset the password for the following site and username.') . "\n\n" .
                __('Site:') . ' ' . App::blog()->name() . ' - ' . App::blog()->url() ."\n" .
                __('Username:') . ' ' . $user_id . "\n\n" .
                __('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.') . "\n" .
                App::blog()->url() . App::url()->getURLFor(My::id()) . '/' . My::ACTION_RECOVER . '/' . $user_key . "\n",
                80
            )
        );
    }

    /**
     * Send recovery password email.
     */
    public static function sendPasswordMail(string $user_id, string $user_pwd, string $user_email): void
    {
        self::mailSender(
            $user_email,
            __('Your new password'),
            wordwrap(
                __('Someone has requested to reset the password for the following site and username.') . "\n\n" .
                __('Site:') . ' ' . App::blog()->name() . ' - ' . App::blog()->url() ."\n" .
                __('Username:') . ' ' . $user_id . "\n" .
                __('Password:') . ' ' . $user_pwd . "\n" .
                __('To change this password visit the following address and sign in with these idents.') . "\n" .
                App::blog()->url() . App::url()->getURLFor(My::id()) . "\n",
                80
            )
        );
    }
}