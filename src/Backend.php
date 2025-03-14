<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Backend\Notices;
use Dotclear\Database\Cursor;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Note,
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
            'initWidgets' => Widgets::initWidgets(...),
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
                                (new Checkbox(My::id() . 'enable_registration', (bool) $blog_settings->get(My::id())->get('enable_registration')))
                                    ->value(1),
                                (new Label(__('Enable user registration form on frontend'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for(My::id() . 'enable_registration'),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'enable_recovery', (bool) $blog_settings->get(My::id())->get('enable_recovery')))
                                    ->value(1),
                                (new Label(__('Enable user password recovery form on frontend'), Label::OUTSIDE_LABEL_AFTER))
                                    ->class('classic')
                                    ->for(My::id() . 'enable_recovery'),
                            ]),
                        (new Para())->items([
                            (new Label(__('Registration administrator email:')))->for(My::id() . 'email_registration'),
                            (new Input(My::id() . 'email_registration'))->class('maximal')->size(65)->maxlength(255)->value($blog_settings->get(My::id())->get('email_registration')),
                        ]),
                        (new Note())->class('form-note')->text(__('This is the comma separeted list of administrator mail address who receive new registration notification.')),
                        (new Para())->items([
                            (new Label(__('Registration no-reply email:')))->for(My::id() . 'email_from'),
                            (new Input(My::id() . 'email_from'))->class('maximal')->size(65)->maxlength(255)->value($blog_settings->get(My::id())->get('email_from')),
                        ]),
                        (new Note())->class('form-note')->text(__('This is mail address used on registration confirmation email.')),
                        (new Para())
                            ->items([
                                (new Textarea(My::id() . 'connected', Html::escapeHTML((string) $blog_settings->get(My::id())->get('connected'))))
                                    ->rows(6)
                                    ->class('maximal')
                                    ->label((new Label(__('Text to display on login page when user is connected:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Textarea(My::id() . 'disconnected', Html::escapeHTML((string) $blog_settings->get(My::id())->get('disconnected'))))
                                    ->rows(6)
                                    ->class('maximal')
                                    ->label((new Label(__('Text to display on login page when user is disconnected:'), Label::OL_TF))),
                            ]),
                    ])
                    ->render();
            },
            // blog settings update
            'adminBeforeBlogSettingsUpdate' => function (BlogSettingsInterface $blog_settings): void {
                $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']));
                $blog_settings->get(My::id())->put('enable_registration', !empty($_POST[My::id() . 'enable_registration']));
                $blog_settings->get(My::id())->put('enable_recovery', !empty($_POST[My::id() . 'enable_recovery']));
                $blog_settings->get(My::id())->put('email_registration', (string) $_POST[My::id() . 'email_registration']);
                $blog_settings->get(My::id())->put('email_from', (string) $_POST[My::id() . 'email_from']);
                $blog_settings->get(My::id())->put('connected', $_POST[My::id() . 'connected']);
                $blog_settings->get(My::id())->put('disconnected', $_POST[My::id() . 'disconnected']);
            },
            // add js for test editor
            'adminBlogPreferencesHeaders' => fn (): string => My::jsLoad('backend'),
            // add our textarea form ID to post editor
            'adminPostEditorTags' => function (string $editor, string $context, ArrayObject $alt_tags, string $format): void {
                // there is an existsing postEditor on this page, so we add our textarea to it
                if ($context === 'blog_desc') {
                    $alt_tags->append('#' . My::id() . 'connected');
                    $alt_tags->append('#' . My::id() . 'disconnected');
                }
            },
            // simple menu type
            'adminSimpleMenuAddType' => function (ArrayObject $items): void {
                if (My::settings()->get('active')) {
                    $items[My::id()] = new ArrayObject([__('Public login page'), false]);
                }
            },
            // simple menu select
            'adminSimpleMenuBeforeEdit' => function ($type, $select, &$attr): void {
                if ($type == My::id()) {
                    $attr[0] = __('Connexion');
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
            'adminBeforeUserUpdate' => function (Cursor $cur, string $user_id): void {
                $user = App::users()->getUsers(['user_id' => $user_id, 'user_status' => My::USER_PENDING]);
                if (!$user->isEmpty() && $cur->user_status == App::status()->user()::ENABLED) {
                    Mail::sendActivationMail($user->user_email);
                }
            },
            'adminBeforeUserEnable' => function (string $user_id): void {
                $user = App::users()->getUsers(['user_id' => $user_id, 'user_status' => My::USER_PENDING]);
                if (!$user->isEmpty()) {
                    Mail::sendActivationMail($user->user_email);
                }
            },
        ]);

        return true;
    }
}
