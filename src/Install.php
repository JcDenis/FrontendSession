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
            My::settings()->put('active', false, App::blogWorkspace()::NS_BOOL, 'Enable public sessions', false, true);
            My::settings()->put('enable_registration', false, App::blogWorkspace()::NS_BOOL, 'Enable user registration form on frontend', false, true);
            My::settings()->put('enable_recovery', false, App::blogWorkspace()::NS_BOOL, 'Enable user password recovery form on frontend', false, true);
            My::settings()->put('limit_comment', false, App::blogWorkspace()::NS_BOOL, 'Limit new comments to registered users', false, true);
            My::settings()->put('condition_page', '', App::blogWorkspace()::NS_STRING, 'Use link to terms and conditions page on signup form', false, true);
            My::settings()->put('disable_css', false, App::blogWorkspace()::NS_BOOL, 'Disable default CSS', false, true);
            My::settings()->put('log_form_error', false, App::blogWorkspace()::NS_BOOL, 'Log frontend forms submissions errors', false, true);
            My::settings()->put('email_registration', '', App::blogWorkspace()::NS_STRING, 'Email to send registration confirmation to', false, true);
            My::settings()->put('email_from', '', App::blogWorkspace()::NS_STRING, 'No-reply email address for confirmation mail', false, true);
            My::settings()->put('connected', "You're now connected to the blog.", App::blogWorkspace()::NS_STRING, 'Connected display text', false, true);
            My::settings()->put('disconnected', "You must be connected to unlock all blog's features.", App::blogWorkspace()::NS_STRING, 'Disconnected display text', false, true);
            My::settings()->put('post_format', '', App::blogWorkspace()::NS_STRING, 'Post content syntax for new users', false, true);

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
