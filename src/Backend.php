<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Throwable;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\Cursor;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Interface\Core\BlogSettingsInterface;

/**
 * @brief       FrontendSession backend class.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class Backend
{
    use TraitProcess;

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
            // add script for pending count on dashboard icon
            'adminDashboardHeaders' => fn (): string => Page::jsJson('FrontendSession', [
                'interval' => 30,
                'status'   => My::USER_PENDING,
            ]) .
                My::jsLoad('backend-dashboard'),
            // blog settings form
            'adminBlogPreferencesFormV2' => function (BlogSettingsInterface $blog_settings): void {
                // From user preferences (might be optimized, one day)
                $formaters         = App::formater()->getFormaters();
                $format_by_editors = [];
                foreach ($formaters as $editor => $formats) {
                    $label = __((string) App::plugins()->moduleInfo($editor, 'desc')) ?: __($editor);
                    foreach ($formats as $format) {
                        $format_by_editors[$format][$label] = $editor;
                    }
                }
                $available_formats = ['' => ''];
                foreach (array_keys($format_by_editors) as $format) {
                    $available_formats[App::formater()->getFormaterName($format)] = $format;
                    if (!isset($user_options['editor'][$format])) {
                        $user_options['editor'][$format] = '';
                    }
                }

                echo (new Fieldset(My::id() . '_params'))
                    ->legend(new Legend((new Img(My::icons()[0]))->class('icon-small')->render() . ' ' . My::name()))
                    ->items([
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'active', (bool) $blog_settings->get(My::id())->get('active')))
                                    ->value(1)
                                    ->label(new Label(__('Enable sessions on frontend'), Label::IL_FT)),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'enable_registration', (bool) $blog_settings->get(My::id())->get('enable_registration')))
                                    ->value(1)
                                    ->label(new Label(__('Enable user registration form on frontend'), Label::IL_FT)),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'enable_recovery', (bool) $blog_settings->get(My::id())->get('enable_recovery')))
                                    ->value(1)
                                    ->label(new Label(__('Enable user password recovery form on frontend'), Label::IL_FT)),
                            ]),
                        (new Para())
                            ->class('field')
                            ->items([
                                (new Select(My::id() . 'post_format'))
                                    ->items($available_formats)
                                    ->default($blog_settings->get(My::id())->get('post_format'))
                                    ->label(new Label(__('Preferred format:'), Label::OL_TF)),
                            ]),
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'limit_comment', (bool) $blog_settings->get(My::id())->get('limit_comment')))
                                    ->value(1)
                                    ->label(new Label(__('Limit new comments to registered users'), Label::IL_FT)),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('All themes are not necessarily compatible with this feature.')),
                        (new Para())
                            ->items([
                                (new Checkbox(My::id() . 'disable_css', (bool) $blog_settings->get(My::id())->get('disable_css')))
                                    ->value(1)
                                    ->label(new Label(__('Disable default CSS'), Label::IL_FT)),
                            ]),
                        (new Para())
                            ->items([
                                (new Input(My::id() . 'condition_page'))
                                    ->size(65)
                                    ->maxlength(255)
                                    ->value($blog_settings->get(My::id())->get('condition_page'))
                                    ->label(new Label(sprintf(__('Link to the "%s" page or entry:'), __('Terms and Conditions')), Label::OL_TF)),
                                (new Button('condition_page_selector', __('Choose an entry'))),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Leave this field empty if you do not have a "%s" page.'), __('Terms and Conditions'))),
                        (new Para())
                            ->items([
                                (new Input(My::id() . 'email_registration'))
                                    ->class('maximal')
                                    ->size(65)
                                    ->maxlength(255)
                                    ->value($blog_settings->get(My::id())->get('email_registration'))
                                    ->label(new Label(__('Registration administrator email:'), Label::OL_TF)),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('This is the comma separeted list of administrator mail address who receive new registration notification.')),
                        (new Para())
                            ->items([
                                (new Input(My::id() . 'email_from'))
                                    ->class('maximal')
                                    ->size(65)
                                    ->maxlength(255)
                                    ->value($blog_settings->get(My::id())->get('email_from'))
                                    ->label(new Label(__('Registration no-reply email:'), Label::OL_TF)),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('This is mail address used on registration confirmation email.')),
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
                $blog_settings->get(My::id())->put('active', !empty($_POST[My::id() . 'active']), 'boolean');
                $blog_settings->get(My::id())->put('enable_registration', !empty($_POST[My::id() . 'enable_registration']), 'boolean');
                $blog_settings->get(My::id())->put('enable_recovery', !empty($_POST[My::id() . 'enable_recovery']), 'boolean');
                $blog_settings->get(My::id())->put('limit_comment', !empty($_POST[My::id() . 'limit_comment']), 'boolean');
                $blog_settings->get(My::id())->put('disable_css', !empty($_POST[My::id() . 'disable_css']), 'boolean');
                $blog_settings->get(My::id())->put('condition_page', (string) $_POST[My::id() . 'condition_page'], 'text');
                $blog_settings->get(My::id())->put('email_registration', (string) $_POST[My::id() . 'email_registration'], 'text');
                $blog_settings->get(My::id())->put('email_from', (string) $_POST[My::id() . 'email_from'], 'text');
                $blog_settings->get(My::id())->put('connected', (string) $_POST[My::id() . 'connected'], 'text');
                $blog_settings->get(My::id())->put('disconnected', (string) $_POST[My::id() . 'disconnected'], 'text');
                $blog_settings->get(My::id())->put('post_format', (string) $_POST[My::id() . 'post_format'], 'text');
            },
            // add js for test editor
            'adminBlogPreferencesHeaders' => fn (): string => My::jsLoad('backend-blogpref') . Page::jsJson(My::id(), [
                'popup_posts' => App::backend()->url()->get('admin.posts.popup', [
                    'popup'     => 1,
                    'plugin_id' => My::id(),
                    'type'      => 'page',
                ], '&'),
            ]),
            // add blog setting form input sibling
            'adminPopupPosts' => fn (string $plugin_id): string => $plugin_id !== My::id() ? '' :
                    Page::jsJson('admin.blog_pref', [
                        'base_url' => App::blog()->url(),
                        'sibling'  => My::id() . 'condition_page',
                    ]) .
                    Page::jsLoad('js/_blog_pref_popup_posts.js'),
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
                    $attr[0] = __('My account');
                    $attr[1] = __('Sign in to this blog');
                    $attr[2] = App::blog()->url() . App::url()->getURLFor(My::id());
                }
            },
            // user activation
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
            // send mail on user activation
            'adminBeforeUserUpdate' => function (Cursor $cur, string $user_id): void {
                $user = App::users()->getUsers(['user_id' => $user_id, 'user_status' => My::USER_PENDING]);
                if (!$user->isEmpty() && $cur->user_status == App::status()->user()::ENABLED) {
                    Mail::sendActivationMail($user->user_email);
                }
            },
            // send mail on user activation
            'adminBeforeUserEnable' => function (string $user_id): void {
                $user = App::users()->getUsers(['user_id' => $user_id, 'user_status' => My::USER_PENDING]);
                if (!$user->isEmpty()) {
                    Mail::sendActivationMail($user->user_email);
                }
            },
        ]);

        // add REST methods
        App::rest()->addFunction('FrontendSessionPendingCount', function (): array {
            $count = (int) App::users()->getUsers(['user_status' => My::USER_PENDING], true)->f(0);

            return [
                'ret' => true,
                'msg' => $count !== 0 ? sprintf(__('One pending registration', '%s pending registrations', (int) $count), $count) : '',
                'nb'  => $count,
            ];
        });

        return true;
    }
}
