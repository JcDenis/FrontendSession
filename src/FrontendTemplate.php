<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Tpl;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Form, Hidden, Input, Label, Note, Para, Password, Submit, Text };
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

/**
 * @brief       FrontendSession module template specifics.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendTemplate
{
    /**
     * Generic filter helper.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    private static function filter(ArrayObject $attr, string $res): string
    {
        return '<?php echo ' . sprintf(App::frontend()->template()->getFilters($attr), $res) . '; ?>';
    }

    /**
     * Check conditions.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionIf(ArrayObject $attr, string $content): string
    {
        $if   = [];
        $sign = fn ($a): string => (bool) $a ? '' : '!';

        $operator = isset($attr['operator']) ? Tpl::getOperator($attr['operator']) : '&&';

        // allow registration
        if (isset($attr['registration'])) {
            $if[] = $sign($attr['registration']) . My::class . "::settings()->get('enable_registration')";
        }
        // allow password recovery
        if (isset($attr['recovery'])) {
            $if[] = $sign($attr['recovery']) . My::class . "::settings()->get('enable_recovery')";
        }
        // session state
        if (isset($attr['state'])) {
            $if[] = $sign($attr['state']) . "(App::frontend()->context()->frontend_session?->state == '" . Html::escapeHTML($attr['state']) . "')";
        }
        // session success message
        if (isset($attr['success'])) {
            $if[] = $sign($attr['success']) . "(App::frontend()->context()->frontend_session?->success != '')";
        }

        return $if === [] ?
            $content :
            '<?php if(' . implode(' ' . $operator . ' ', $if) . ') : ?>' . $content . '<?php endif; ?>';
    }

    /**
     * Get module ID.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionNonce(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::nonce()->getNonce()');
    }

    /**
     * Get session page URL.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionUrl(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::blog()->url().App::url()->getURLFor(' . My::class . '::id())' . (empty($attr['signout']) ? '' : ".'/'." . My::class . '::ACTION_SIGNOUT'));
    }

    /**
     * Get user display name.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionUser(ArrayObject $attr): string
    {
        return self::filter($attr, "(App::auth()->userID() != '' ? App::auth()->getInfo('user_cn') : '')");
    }

    /**
     * Get text when action succeed.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionSuccess(ArrayObject $attr): string
    {
        return self::filter($attr, "App::frontend()->context()->frontend_session?->success ?: ''");
    }

    /**
     * Get session page text when user is (dis)connected.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionInfo(ArrayObject $attr): string
    {
        return self::filter($attr, My::class . "::settings()->get(App::auth()->userID() == '' ? 'disconnected' : 'connected')");
    }

    /**
     * Frontend session page content.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionContent(ArrayObject $attr): string
    {
        return '<?php ' . self::class . '::parseSessionPage(); ?>';
    }

    /**
     * Parse frontend session page content.
     */
    public static function parseSessionPage(): void
    {
        if (!is_a(App::frontend()->context()->frontend_session, FrontendSession::class)) {
            return;
        }

        $connected = App::auth()->check(My::id(), App::blog()->id());
        $forms     = [];

        $hidden = fn (string $action): array => [
            (new Hidden([My::id() . 'redir'], Http::getSelfURI())),
            (new Hidden([My::id() . 'check'], App::nonce()->getNonce())),
            (new Hidden([My::id() . 'action'], $action)),
        ];
        $form = fn (string $action, string $title, array $items): Div => 
            (new Div(My::id() . $action))
                ->items([
                    (new Text('h3', $title)),
                    (new Form(My::id() . $action . 'form'))
                        ->class('session-form')
                        ->action('')
                        ->method('post')
                        ->fields($items),
                ]);

        // Sign in form
        if (!$connected) {
            $action  = My::ACTION_SIGNIN;
            $forms[] = $form($action, __('Sign in'), [
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_login'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->autocomplete('username')
                            ->label(new Label(__('Login:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_password'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->autocomplete('current-password')
                            ->label(new Label(__('Password:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class('inputfield')
                    ->items([
                        (new Checkbox(My::id() . $action . '_remember'))
                            ->label(new Label(__('Remenber me'), Label::OL_FT)),
                    ]),
                (new Div())
                    ->class('controlset')
                    ->items([
                        (new Submit(My::id() . $action . 'save', __('Sign in')))
                            ->class('button'),
                        ... $hidden($action),
                    ]),
            ]);
        }

        // sign up form
        if (!$connected && My::settings()->get('enable_registration')) {
            $action  = My::ACTION_SIGNUP;
            $forms[] = $form($action, __('Sign up'), [
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_login'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->autocomplete('username')
                            ->label(new Label(__('Username:'), Label::OL_TF)),
                        (new Note())
                            ->class('note')
                            ->text(__('Should be at least 3 characters long with only figures and letters.')),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_firstname'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('First Name:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_name'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Last Name:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_email'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Email:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_vemail'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Repeat email:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_password'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Password:'), Label::OL_TF)),
                        (new Note())
                            ->class('note')
                            ->text(__('Should be at least 6 characters long.')),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_vpassword'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Repeat password:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class('controlset')
                    ->items([
                        (new Submit(My::id() . $action . 'save', __('Sign up')))
                            ->class('button'),
                        ... $hidden($action),
                    ]),
            ]);
        }

        // password recovery form
        if (!$connected && My::settings()->get('enable_recovery')) {
            $action  = My::ACTION_RECOVER;
            $forms[] = $form($action, __('Password recovery'), [
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_usermail'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Username:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Input(My::id() . $action . '_email'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Email:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class('controlset')
                    ->items([
                        (new Submit(My::id() . $action . 'save', __('Recover')))
                            ->class('button'),
                        ... $hidden($action),
                    ]),
            ]);
        }

        // signout form
        if ($connected) {
            $action  = My::ACTION_SIGNOUT;
            $forms[] = $form($action, __('Sign in'), [
                (new Text('p', sprintf(__('You are connected as: %s'), App::auth()->getInfo('user_cn')))),
                (new Div())
                    ->class('controlset')
                    ->items([
                        (new Submit(My::id() . $action . 'save', __('Logout')))
                            ->class('button'),
                        ... $hidden($action),
                    ]),
            ]);
        }

        // password recovery change form
        if (App::frontend()->context()->frontend_session->state == My::STATE_CHANGE && My::settings()->get('enable_recovery')) {
            $action  = My::ACTION_CHANGE;
            $forms[] = $form($action, __('Password change'), [
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_password'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('New password:'), Label::OL_TF)),
                        (new Note())
                            ->class('note')
                            ->text(__('Should be at least 6 characters long.')),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_vpassword'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Repeat new password:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class('controlset')
                    ->items([
                        (new Submit(My::id() . $action . 'save', __('Change')))
                            ->class('button'),
                        (new Hidden([My::id() . $action . '_data'], App::frontend()->context()->frontend_session->data ?? '')),
                        ... $hidden($action),
                    ]),
            ]);
        }

        // Password change form
        if ($connected && App::auth()->allowPassChange()){//} && !App::auth()->check(App::auth()::PERMISSION_ADMIN, App::blog()->id())) {
            // admins MUST use backend methods to change password
            $action  = My::ACTION_UPDPASS;
            $forms[] = $form($action, __('Password change'), [
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_current'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Current password:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_newpass'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('New password:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class(['inputfield', 'required'])
                    ->items([
                        (new Password(My::id() . $action . '_vrfpass'))
                            ->size(30)
                            ->maxlength(255)
                            ->value('')
                            ->required(true)
                            ->label(new Label(__('Repeat new password:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class('controlset')
                    ->items([
                        (new Submit(My::id() . $action . 'save', __('Save'))),
                        ... $hidden($action),
                    ]),
            ]);
        }

        // User pref from
        if ($connected) {
            $action  = My::ACTION_UPDPREF;
            $forms[] = $form($action, __('Profil'), [
                // user_site
                (new Div())
                    ->class('inputfield')
                    ->items([
                        (new Input(My::id() . $action . '_url'))
                            ->size(30)
                            ->maxlength(255)
                            ->value(Html::escapeHTML(App::auth()->getInfo('user_url')))
                            ->label(new Label(__('Your site URL:'), Label::OL_TF)),
                    ]),
                (new Div())
                    ->class('controlset')
                    ->items([
                        (new Submit(My::id() . $action . 'save', __('Save'))),
                        ... $hidden($action),
                    ]),
            ]);
        }

        echo (new Para)
            ->items($forms)
            ->render();
    }
}
