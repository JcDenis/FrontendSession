<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Input,
    Label,
    Password,
    Para,
    Submit,
    Text
};
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
                self::FrontendSessionWidget(...),
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

        if (App::auth()->userID() != '') {
            // signout
            $form = (new Form())
                    ->method('post')
                    ->action($url)
                    ->id(My::id() . 'widget_signout_form')
                    ->fields([
                        (new Text('p', __('You are connected as:') . '<br />' . App::auth()->getInfo('user_cn'))),
                        (new Para())
                            ->items([
                                App::nonce()->formNonce(),
                                (new Hidden([My::id() . 'action', My::id() . 'widget_signout_action'], My::ACTION_SIGNOUT)),
                                (new Submit([My::id() . 'submit', My::id() . 'widget_signout_submit'], __('Disconnect'))),
                            ]),
                    ]);
        } else {
            // signin
            $form = (new Form())
                    ->method('post')
                    ->action($url)
                    ->id(My::id() . 'widget_form')
                    ->fields([
                        (new Para())
                            ->items([
                                (new Input([My::id() . 'signin_login', My::id() . 'widget_signin_login']))
                                    ->class('maximal')
                                    ->maxlength(255)
                                    ->autocomplete('username')
                                    ->label((new Label(__('Login:'), Label::INSIDE_TEXT_BEFORE))->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Password([My::id() . 'signin_password', My::id() . 'widget_signin_password']))
                                    ->class('maximal')
                                    ->maxlength(255)
                                    ->autocomplete('current-password')
                                    ->label((new Label(__('Password:'), Label::INSIDE_TEXT_BEFORE))->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox([My::id() . 'signin_remember', My::id() . 'widget_singin_remember']))
                                    ->label((new Label(__('Remenber me'), Label::INSIDE_LABEL_AFTER))->class('classic')),
                            ]),
                        (new Para())
                            ->items([
                                App::nonce()->formNonce(),
                                (new Hidden([My::id() . 'redir', My::id() . 'widget_signin_redir'], Http::getSelfURI())),
                                (new Hidden([My::id() . 'action', My::id() . 'widget_signout_action'], My::ACTION_SIGNIN)),
                                (new Submit([My::id() . 'submit', My::id() . 'widget_signout_submit'], __('Connect'))),
                            ]),
                    ]);
        }

        return $widget->renderDiv(
            (bool) $widget->get('content_only'),
            My::id() . ' ' . $widget->get('class'),
            '',
            $widget->renderTitle($widget->get('title')) . (new Div())->items([$form])->render()
        );
    }
}
