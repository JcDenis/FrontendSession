<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;

/**
 * @brief       FrontendSession module URL handler.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class UrlHandler extends Url
{
    /**
     * Session login endpoint.
     * 
     * User sign in, sign up, sign out.
     */
    public static function sessionSign(?string $args): void
    {
        if (!My::settings()->get('active')) {
            self::p404();
        }

        $action = '';

        // action from URL
        if (!is_null($args)) {
            $args = substr($args, 1);
            $args = explode('/', $args);
            $action = $args[0];
        }

        // logout
        if ($action == 'signout') {
            Frontend::resetCookie();
            App::blog()->triggerBlog();
            Http::redirect(App::blog()->url());
        // reponse from user pending activation
        } elseif ($action == 'pending' && App::auth()->userID() == '') {
            App::frontend()->context()->form_error = __("Error: your account is not yet activated.");
            self::serveTemplate(My::id() . '.html');
        // no session, go to signin page
        } elseif (App::auth()->userID() == '') {
            self::serveTemplate(My::id() . '.html');
        // all others cases go to signin page
        } else {
            self::serveTemplate(My::id() . '.html');
            //self::p404();
        }
    }

    /**
     * Serve template.
     */
    private static function serveTemplate(string $tpl): void
    {
        // use only dotty tplset
        $tplset = App::themes()->moduleInfo(App::blog()->settings()->get('system')->get('theme'), 'tplset');
        if ($tplset != 'dotty') {
            self::p404();
        }

        $default_template = Path::real(App::plugins()->moduleInfo(My::id(), 'root')) . DIRECTORY_SEPARATOR . Utility::TPL_ROOT . DIRECTORY_SEPARATOR;
        if (is_dir($default_template . $tplset)) {
            App::frontend()->template()->setPath(App::frontend()->template()->getPath(), $default_template . $tplset);
        }

        self::serveDocument($tpl);
    }
}
