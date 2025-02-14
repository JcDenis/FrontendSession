<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\widgets\WidgetsElement;
use Dotclear\Plugin\widgets\WidgetsStack;

/**
 * @brief       FrontendSession module widgets helper.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Widgets
{
    /**
     * Initializes module widget.
     */
    public static function initWidgets(WidgetsStack $widgets): void
    {
        $widgets
            ->create(
                'FrontendSession',
                __('Frontend session'),
                [self::class, 'FrontendSessionWidget'],
                null,
                'Public login form'
            )
            ->addTitle(__('Login'))
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    /**
     * Widget public rendering helper for public login and menu.
     */
    public static function FrontendSessionWidget(WidgetsElement $widget): string
    {
        if ($widget->isOffline() || !My::settings()->get('active')) {
            return '';
        }

        $url = App::blog()->url() . App::url()->getURLFor(My::id());
        $res = $widget->renderTitle($widget->get('title'));

        if (App::auth()->userID() != '') {
            // signout
            $res .= '<p>' . __('You are connected as:') . '<br />' . App::auth()->getInfo('user_cn') . '<p>' .
                '<form method="post" name="' . My::id() . 'signout_form" id="' . My::id() . 'widget_signout_form" action="' . $url . '">' .
                '<p>' .
                    '<input type="hidden" id="' . My::id() . 'widget_signout_action" name="' . My::id() . 'action" value="' . My::ACTION_SIGNOUT . '" />' .
                    '<input type="hidden" id="' . My::id() . 'widget_signout_check" name="' . My::id() . 'check" value="' . App::nonce()->getNonce() . '" />' .
                    '<input class="submit" type="submit" id="' . My::id() . 'widget_signout_submit" name="' . My::id() . 'submit" value="' . __('Disconnect') . '" />' .
                '</p>' .
                '</form>';
        } else {
            // signin
            $res .= '<form method="post" name="' . My::id() . 'form" id="' . My::id() . 'widget_form" action="' . $url . '">' .
                '<p>' .
                    '<label for="' . My::id() . 'widget_signin_login" class="required">' . __('Login:') . '</label><br />' .
                    '<input type="text"  autocomplete="username" id="' . My::id() . 'widget_signin_login" name="' . My::id() . 'signin_login" value="" />' .
                '</p>' .
                '<p>' .
                    '<label for="' . My::id() . 'widget_signin_password" class="required">' . __('Password:') . '</label><br />' .
                    '<input type="password"  autocomplete="current-password" id="' . My::id() . 'widget_signin_password" name="' . My::id() . 'signin_password" value="" />' .
                '</p>' .
                '<p>' .
                '<label><input type="checkbox" id="' . My::id() . 'widget_singin_remember" name="' . My::id() . 'signin_remember" value="1"> ' .
                __('Remenber me') . '</label></p>' .
                '<p>' .
                    '<input type="hidden" id="' . My::id() . 'widget_signin_action" name="' . My::id() . 'action" value="' . My::ACTION_SIGNIN . '" />' .
                    '<input type="hidden" id="' . My::id() . 'widget_signin_redir" name="' . My::id() . 'redir" value="' . Http::getSelfURI() . '" />' .
                    '<input type="hidden" id="' . My::id() . 'widget_signin_check" name="' . My::id() . 'check" value="' . App::nonce()->getNonce() . '" />' .
                    '<input class="submit" type="submit" id="' . My::id() . 'widget_singin_submit" name="' . My::id() . 'submit" value="' . __('Connect') . '" />' .
                '</p>' .
                '</form>';
        }

        return $widget->renderDiv((bool) $widget->get('content_only'), My::id() . ' ' . $widget->get('class'), '', $res);
    }
}
