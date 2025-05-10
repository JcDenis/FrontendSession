<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Schema\Extension\PostPublic;

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
        // If post comment is closed, keep it closed
        if (!$rs->f('post_open_comment')) {

            return false;
        }

        $option = new CommentOptions($rs);

        # --BEHAVIOR-- FrontendSessionCommentsActive -- CommentOptions
        App::behavior()->callBehavior('FrontendSessionCommentsActive', $option);

        // check third party plugins
        if (is_bool($option->isActive())) {
            return $option->isActive();
        }

        // at least check frontent session settings, then Doclear settings
        return (!My::settings()->get('limit_comment') || App::auth()->check(My::id(), App::blog()->id())) && PostPublic::commentsActive($rs);
    }
}
