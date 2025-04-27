<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

/**
 * @brief       daRepo module record extension.
 * @ingroup     daRepo
 *
 * @author      Dotclear team
 * @copyright   AGPL-3.0
 */
class RecordExtendPost
{
    /**
     * Returns whether comments are enabled on post.
     * 
     * This overloads Dotclear\Schema\Extension\Post::commentsActive()
     *
     * @param      MetaRecord  $rs     Invisible parameter
     */
    public static function commentsActive(MetaRecord $rs): bool
    {
        return (!My::settings()->get('limit_comment') || My::settings()->get('limit_comment') && App::auth()->check(My::id(), App::blog()->id()))
            && App::blog()->settings()->system->allow_comments
            && $rs->post_open_comment
            && (App::blog()->settings()->system->comments_ttl == 0 || time() - (App::blog()->settings()->system->comments_ttl * 86400) < $rs->getTS());
    }
}
