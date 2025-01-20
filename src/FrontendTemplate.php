<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Tpl;
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
        $if = [];
        $sign = fn ($a): string => (bool) $a ? '' : '!';

        $operator = isset($attr['operator']) ? Tpl::getOperator($attr['operator']) : '&&';

        // allow registration
        if (isset($attr['registration'])) {
            $if[] = $sign($attr['registration']) . My::CLASS . "::settings()->get('enable_registration')";
        }
        // allow password recovery
        if (isset($attr['recovery'])) {
            $if[] = $sign($attr['recovery']) . My::CLASS . "::settings()->get('enable_recovery')";
        }
        // session state
        if (isset($attr['state'])) {
            $if[] = "App::frontend()->context()->session_state == '" . Html::escapeHTML($attr['state']) . "'";
        }

        return empty($if) ?
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
     * Get module ID.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionID(ArrayObject $attr): string
    {
        return self::filter($attr, My::class . '::id()');
    }

    /**
     * Get session page URL.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionUrl(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::blog()->url().App::url()->getURLFor(' . My::class . '::id())' . (!empty($attr['signout']) ? ".'/signout'" : ''));
    }

    /**
     * Get session page text when user is (dis)connected.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionMessage(ArrayObject $attr): string
    {
        return self::filter($attr, My::CLASS . "::settings()->get(App::auth()->userID() == '' ? 'disconnected' : 'connected')");
    }

    /**
     * Get user display name.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionDisplayName(ArrayObject $attr): string
    {
        return self::filter($attr, "(App::auth()->userID() != '' ? App::auth()->getInfo('user_cn') : '')");
    }

    /**
     * Get session data for password changes.
     *
     * @param   ArrayObject<string, mixed>  $attr       The attributes
     */
    public static function FrontendSessionData(ArrayObject $attr): string
    {
        return self::filter($attr, 'App::frontend()->context()->session_data ?? ""');
    }
}
