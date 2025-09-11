<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
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
            ->addTitle(__('My account'))
            ->setting(
                'show',
                __('Content:'),
                '*',
                'combo',
                [
                    __('Form and menu') => '*',
                    __('Only form')     => 'form',
                    __('Only menu')     => 'menu',
                ]
            )
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
        if ($widget->isOffline()
            || !$widget->checkHomeOnly(App::url()->getType())
            || !My::settings()->get('active')
        ) {
            return '';
        }

        /**
         * @var     ArrayObject<int, Li>    $lines
         */
        $lines = new ArrayObject();

        $form = false;
        $url  = App::blog()->url() . App::url()->getURLFor(My::id());

        # --BEHAVIOR-- FrontendSessionWidget -- ArrayObject, string, WidgetsElement
        App::behavior()->callBehavior('FrontendSessionWidget', $lines, $url, $widget);

        if (App::auth()->userID() != '') {
            if ($widget->get('show') != 'form') {

                $lines[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($url)
                            ->text(__('My account')),
                    ]);
            }

            // signout
            if ($widget->get('show') != 'menu') {
                $form = (new Form())
                    ->class('session-form')
                    ->method('post')
                    ->action($url)
                    ->id(My::id() . My::ACTION_SIGNOUT . 'form_widget')
                    ->fields([
                        (new Text('p', __('You are connected as:') . '<br />' . App::auth()->getInfo('user_cn'))),
                        (new Para())
                            ->items([
                                (new Hidden([My::id() . 'check'], App::nonce()->getNonce())),
                                (new Hidden([My::id() . 'state'], '')),
                                (new Hidden([My::id() . 'action', My::id() . My::ACTION_SIGNOUT . 'action_widget'], My::ACTION_SIGNOUT)),
                                (new Submit([My::id() . 'submit', My::id() . My::ACTION_SIGNOUT . 'submit_widget'], __('Disconnect'))),
                            ]),
                    ]);
                }
        } elseif ($widget->get('show') != 'menu') {
            if (My::settings()->get('enable_recovery')) {
                $lines[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($url . '#' . My::id() . My::ACTION_RECOVER)
                            ->text(__('Password recovery')),
                    ]);
            }
            if (My::settings()->get('enable_registration')) {
                $lines[] = (new Li())
                    ->items([
                        (new Link())
                            ->href($url . '#' . My::id() . My::ACTION_SIGNUP)
                            ->text(__('Sign up')),
                    ]);
            }

            // signin
            $form = (new Form())
                    ->class('session-form')
                    ->method('post')
                    ->action($url)
                    ->id(My::id() . My::ACTION_SIGNIN . 'form_widget')
                    ->fields([
                        (new Para())
                            ->items([
                                (new Input([My::id() . My::ACTION_SIGNIN . '_login', My::id() . My::ACTION_SIGNIN . '_login_widget']))
                                    ->maxlength(255)
                                    ->autocomplete('username')
                                    ->label((new Label(__('Login:'), Label::OL_TF))->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Password([My::id() . My::ACTION_SIGNIN . '_password', My::id() . My::ACTION_SIGNIN . '_password_widget']))
                                    ->maxlength(255)
                                    ->autocomplete('current-password')
                                    ->label((new Label(__('Password:'), Label::OL_TF))->class('required')),
                            ]),
                        // Honeypot
                        (new Para())
                            ->extra('style="display:none;"')
                            ->items([
                                (new Email(['email']))
                                    ->value(''),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox([My::id() . My::ACTION_SIGNIN . '_remember', My::id() . My::ACTION_SIGNIN . '_remember_widget']))
                                    ->label(new Label(__('Remenber me'), Label::OL_FT)),
                            ]),
                        (new Para())
                            ->items([
                                (new Hidden([My::id() . 'check'], App::nonce()->getNonce())),
                                (new Hidden([My::id() . 'state'], '')),
                                (new Hidden([My::id() . 'redir', My::id() . My::ACTION_SIGNIN . 'redir_widget'], Http::getSelfURI())),
                                (new Hidden([My::id() . 'action', My::id() . My::ACTION_SIGNIN . 'action_widget'], My::ACTION_SIGNIN)),
                                (new Submit([My::id() . 'submit', My::id() . My::ACTION_SIGNIN . 'submit_widget'], __('Connect'))),
                            ]),
                    ]);
        }

        if ($form === false && count($lines) === 0) {

            return '';
        }

        return $widget->renderDiv(
            (bool) $widget->get('content_only'),
            My::id() . ' ' . $widget->get('class'),
            '',
            $widget->renderTitle($widget->get('title')) . ($form === false ? '' : $form->render()) . (count($lines) === 0 ? '' : (new Ul())->items($lines)->render())
        );
    }
}
