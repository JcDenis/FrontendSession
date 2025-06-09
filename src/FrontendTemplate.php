<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Tpl;
use Dotclear\Helper\Html\Form\{ Checkbox, Div, Email, Hidden, Input, Label, Link, None, Note, Password, Text };
use Dotclear\Helper\Html\Html;

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
        // has a condition page link
        if (isset($attr['condition'])) {
            $if[] = $sign($attr['condition']) . My::class . "::settings()->get('condition_page')";
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
        $profil    = new FrontendSessionProfil(My::id());

        // Sign in form
        if (!$connected) {
            $action  = My::ACTION_SIGNIN;
            $profil->addAction($action, __('Sign in'), [
                $profil->getInputfield([
                    (new Input(My::id() . $action . '_login'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('username')
                        ->label(new Label(__('Login:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_password'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('current-password')
                        ->label(new Label(__('Password:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Checkbox(My::id() . $action . '_remember'))
                        ->label(new Label(__('Remenber me'), Label::OL_FT)),
                ], true),
                $profil->getControlset($action, __('Sign in')),
            ]);
        }

        // sign up form
        if (!$connected && My::settings()->get('enable_registration')) {
            $action  = My::ACTION_SIGNUP;
            $profil->addAction($action, __('Sign up'), [
                $profil->getInputfield([
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
                ], true),
                $profil->getInputfield([
                    (new Input(My::id() . $action . '_firstname'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->label(new Label(__('First Name:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Input(My::id() . $action . '_name'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->label(new Label(__('Last Name:'), Label::OL_TF)),
                ], true),
                // Honeypot
                (new Div())
                    ->extra('style="display:none;"')
                    ->items([
                        (new Email('email'))
                            ->value(''),
                    ]),
                $profil->getInputfield([
                    (new Email(My::id() . $action . '_email'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('username')
                        ->label(new Label(__('Email:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Email(My::id() . $action . '_vemail'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('username')
                        ->label(new Label(__('Repeat email:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_password'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('new-password')
                        ->label(new Label(__('Password:'), Label::OL_TF)),
                    (new Note())
                        ->class('note')
                        ->text(__('Should be at least 6 characters long.')),
                ], true),
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_vpassword'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('new-password')
                        ->label(new Label(__('Repeat password:'), Label::OL_TF)),
                ], true),
                My::settings()->get('condition_page') != '' ? $profil->getInputfield([
                    (new Checkbox(My::id() . $action . '_condition'))
                        ->label(new Label(
                            sprintf(
                                __('I have read and accept the %s.'),
                                (new Link())
                                ->class('outgoing')
                                ->href(My::settings()->get('condition_page'))
                                ->text(__('Terms and Conditions'))
                                ->render()
                            ),
                            Label::OL_FT
                        )),
                ], true) : (new None()),
                // Honeypot
                $profil->getInputfield([
                    (new Checkbox('agree', false))
                            ->value('1')
                            ->label(new Label(__('Do not check this box'), Label::OL_FT)),
                ]),
                $profil->getControlset($action, __('Sign up')),
            ]);
        }

        // password recovery form
        if (!$connected && My::settings()->get('enable_recovery')) {
            $action  = My::ACTION_RECOVER;
            $profil->addAction($action, __('Password recovery'), [
                // Honeypot
                (new Div())
                    ->extra('style="display:none;"')
                    ->items([
                        (new Email('email'))
                            ->value(''),
                    ]),
                $profil->getInputfield([
                    (new Email(My::id() . $action . '_usermail'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('username')
                        ->label(new Label(__('Username:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Email(My::id() . $action . '_email'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('username')
                        ->label(new Label(__('Email:'), Label::OL_TF)),
                ], true),
                $profil->getControlset($action, __('Recover')),
            ]);
        }

        // signout form
        if ($connected) {
            $action  = My::ACTION_SIGNOUT;
            $profil->addAction($action, __('Sign in'), [
                (new Text('p', sprintf(__('You are connected as: %s'), App::auth()->getInfo('user_cn')))),
                $profil->getControlset($action, __('Logout')),
            ]);
        }

        // password recovery change form
        if (App::frontend()->context()->frontend_session->state === My::STATE_CHANGE && My::settings()->get('enable_recovery')) {
            $action  = My::ACTION_CHANGE;
            $profil->addAction($action, __('Password change'), [
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_password'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('new-password')
                        ->label(new Label(__('New password:'), Label::OL_TF)),
                    (new Note())
                        ->class('note')
                        ->text(__('Should be at least 6 characters long.')),
                ], true),
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_vpassword'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('new-password')
                        ->label(new Label(__('Repeat new password:'), Label::OL_TF)),
                ], true),
                $profil->getControlset($action, __('Change'), [
                    (new Hidden([My::id() . $action . '_data'], App::frontend()->context()->frontend_session->data ?? ''))
                ]),
            ]);
        }

        // Password change form
        if ($connected && App::auth()->allowPassChange() && !App::auth()->check(App::auth()::PERMISSION_ADMIN, App::blog()->id())) {
            // admins MUST use backend methods to change password
            $action  = My::ACTION_UPDPASS;
            $profil->addAction($action, __('Password change'), [
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_current'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('current-password')
                        ->label(new Label(__('Current password:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_newpass'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('new-password')
                        ->label(new Label(__('New password:'), Label::OL_TF)),
                ], true),
                $profil->getInputfield([
                    (new Password(My::id() . $action . '_vrfpass'))
                        ->size(30)
                        ->maxlength(255)
                        ->value('')
                        ->required(true)
                        ->autocomplete('new-password')
                        ->label(new Label(__('Repeat new password:'), Label::OL_TF)),
                ], true),
                $profil->getControlset($action, __('Save')),
            ]);
        }

        // User pref from
        if ($connected) {
            App::behavior()->callBehavior('FrontendSessionProfil', $profil);
        }

        echo $profil->getActions()->render();
    }
}
