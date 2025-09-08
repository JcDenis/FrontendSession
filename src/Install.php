<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       FrontendSession install class.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            My::settings()->put('active', false, 'boolean', 'Enable public sessions', false, true);
            My::settings()->put('enable_registration', false, 'boolean', 'Enable user registration form on frontend', false, true);
            My::settings()->put('enable_recovery', false, 'boolean', 'Enable user password recovery form on frontend', false, true);
            My::settings()->put('limit_comment', false, 'boolean', 'Limit new comments to registered users', false, true);
            My::settings()->put('condition_page', '', 'text', 'Use link to terms and conditions page on signup form', false, true);
            My::settings()->put('disable_css', false, 'boolean', 'Disable default CSS', false, true);
            My::settings()->put('log_form_error', false, 'boolean', 'Log frontend forms submissions errors', false, true);
            My::settings()->put('email_registration', '', 'text', 'Email to send registration confirmation to', false, true);
            My::settings()->put('email_from', '', 'text', 'No-reply email address for confirmation mail', false, true);
            My::settings()->put('connected', "You're now connected to the blog.", 'text', 'Connected display text', false, true);
            My::settings()->put('disconnected', "You must be connected to unlock all blog's features.", 'text', 'Disconnected display text', false, true);

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
