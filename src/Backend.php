<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\Notices;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Label,
    Para,
    Text,
    Textarea
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Throwable;

/**
 * @brief       FrontendSession backend class.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            // widget
            'initWidgets'                => Widgets::initWidgets(...),
            // blog settings form
            'adminBlogPreferencesFormV2' => function (BlogSettingsInterface $blog_settings): void {
                echo (new Div())
                    ->class('fieldset')
                    ->items([
                        (new Text('h4', My::name()))
                            ->id(My::id() . '_params'),
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'active', (bool) $blog_settings->get(My::id())->get('active')))
                                    ->value(1),
                                (new Label(__('Enable sessions on frontend'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for(My::id() . 'active'),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'active_registration', (bool) $blog_settings->get(My::id())->get('active_registration')))
                                    ->value(1),
                                (new Label(__('Activate registration form on frontend'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for(My::id() . 'active_registration'),
                            ]),
                        (new Para())
                            ->items([
                                (new Textarea(My::id() . 'connected', Html::escapeHTML((string) $blog_settings->get(My::id())->get(My::SESSION_CONNECTED))))
                                    ->rows(6)
                                    ->class('maximal')
                                    ->label((new Label(__('Text to display on login page when user is connected:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Textarea(My::id() . 'disconnected', Html::escapeHTML((string) $blog_settings->get(My::id())->get(My::SESSION_DISCONNECTED))))
                                    ->rows(6)
                                    ->class('maximal')
                                    ->label((new Label(__('Text to display on login page when user is disconnected:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Textarea(My::id() . 'pending', Html::escapeHTML((string) $blog_settings->get(My::id())->get(My::SESSION_PENDING))))
                                    ->rows(6)
                                    ->class('maximal')
                                    ->label((new Label(__('Text to display on login page when user is pending activation:'), Label::OL_TF))),
                            ]),
                    ])
                    ->render();
            },
            // blog settings update
            'adminBeforeBlogSettingsUpdate' => function (BlogSettingsInterface $blog_settings): void {
                $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']));
                $blog_settings->get(My::id())->put('active_registration', !empty($_POST[My::id() . 'active_registration']));
                $blog_settings->get(My::id())->put(My::SESSION_CONNECTED, $_POST[My::id() . 'connected']);
                $blog_settings->get(My::id())->put(My::SESSION_DISCONNECTED, $_POST[My::id() . 'disconnected']);
                $blog_settings->get(My::id())->put(My::SESSION_PENDING, $_POST[My::id() . 'pending']);
            },
            // simple menu type
            'adminSimpleMenuAddType' => function (ArrayObject $items) {
                if (My::settings()->get('active')) {
                    $items[My::id()] = new ArrayObject([__('Public login page'), false]);
                }
            },
            // simple menu select
            'adminSimpleMenuBeforeEdit' => function ($type, $select, &$attr) {
                if ($type == My::id()) {
                    $attr[0] = __('Login');
                    $attr[1] = __('Sign in to this blog');
                    $attr[2] = App::blog()->url() . App::url()->getURLFor(My::id());
                }
            },
            'adminUsersActions' => function (array $users, array $blogs, string $action, string $redir): void {
                if ($action == My::id()) {
                    foreach ($users as $u) {
                        try {
                            $cur              = App::auth()->openUserCursor();
                            $cur->user_status = My::USER_PENDING;
                            App::users()->updUser($u, $cur);
                        } catch (Throwable $e) {
                            App::error()->add($e->getMessage());
                        }
                    }
                    if (!App::error()->flag()) {
                        Notices::addSuccessNotice(__('User has been successfully marked as pending.'));
                        Http::redirect($redir);
                    }
                }
            },
        ]);

        return true;
    }
}
