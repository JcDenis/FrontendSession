<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\widgets\Widgets as dcWidgets;
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
            $res .= '<p>' . __('You are connected as:') . '<br />' . App::auth()->getInfo('user_cn') . '<p>' .
                '<form method="post" name="' . My::id() . 'form" id="' . My::id() . 'widget_form_out" action="' . $url . '">' .
                '<p>' .
                    '<input type="hidden" id="' . My::id() . 'widget_action" name="' . My::id() . 'action" value="' . My::ACTION_SIGNOUT . '" />' .
                    '<input type="hidden" id="' . My::id() . 'widget_check" name="' . My::id() . 'xd_check" value="' . App::nonce()->getNonce() . '" />' .
                    '<input class="submit" type="submit" id="' . My::id() . 'widget_submit" name="' . My::id() . 'submit" value="' . __('Disconnect') . '" />' .
                '</p>' .
                '</form>';
        } else {
            $res .= '<form method="post" name="' . My::id() . 'form" id="' . My::id() . 'widget_form" action="' . $url . '">';
            if (App::frontend()->context()->form_error !== null) {
                //$res .= '<p class="erreur">' . Html::escapeHTML(App::frontend()->context()->form_error) . '</p>';
            }
            $res .= '<p>' .
                    '<label for="' . My::id() . 'login" class="required">' . __('Login:') . '</label><br />' .
                    '<input type="text" id="' . My::id() . '_widget_login" name="' . My::id() . 'login" value="" />' .
                '</p>' .
                '<p>' .
                    '<label for="' . My::id() . 'password" class="required">' . __('Password:') . '</label><br />' .
                    '<input type="password" id="' . My::id() . 'widget_password" name="' . My::id() . 'password" value="" />' .
                '</p>' .
                '<p>' .
                '<label><input type="checkbox" id="' . My::id() . 'widget_remember" name="' . My::id() . 'remember" value="1"> ' .
                __('Remenber me') . '</label></p>' .
                '<p>' .
                    '<input type="hidden" id="' . My::id() . 'widget_action" name="' . My::id() . 'action" value="' . My::ACTION_SIGNIN . '" />' .
                    '<input type="hidden" id="' . My::id() . 'widget_redir" name="' . My::id() . 'redir" value="' . Http::getSelfURI() . '" />' .
                    '<input type="hidden" id="' . My::id() . 'widget_check" name="' . My::id() . 'xd_check" value="' . App::nonce()->getNonce() . '" />' .
                    '<input class="submit" type="submit" id="' . My::id() . 'widget_submit" name="' . My::id() . 'submit" value="' . __('Connect') . '" />' .
                '</p>' .
                '</form>';
        }

        return $widget->renderDiv((bool) $widget->get('content_only'), My::id() . ' ' . $widget->get('class'), '', $res);
    }
}
