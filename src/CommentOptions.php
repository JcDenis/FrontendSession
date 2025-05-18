<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\Database\{ Cursor, MetaRecord };

/**
 * @brief       daRepo comment options.
 * @ingroup     daRepo
 *
 * Comment options for frontend pages using session.
 *
 * All third party plugins that play with public session and comments
 * MUST use behavior FrontendSessionCommentsActive to open/close comments.
 *
 * @see     RecordExtendPost::commentsActive()
 * @see     FrontendBehaviors::publicBeforeCommentCreate()
 *
 * @author      Dotclear team
 * @copyright   AGPL-3.0
 */
class CommentOptions
{
    private ?bool $active   = null;
    private ?bool $moderate = null;

    public function __construct(
        public readonly ?MetaRecord $rs = null,
        public readonly ?Cursor $cur = null
    ) {
    }

    /**
     * Switch comments activation.
     */
    public function setActive(bool $value): self
    {
        $this->active = $value;

        return $this;
    }

    /**
     * Switch comments moderation.
     */
    public function setModerate(bool $value): self
    {
        $this->moderate = $value;

        return $this;
    }

    /**
     * Check comments activation.
     */
    public function isActive(): ?bool
    {
        return $this->active;
    }

    /**
     * Check comments moderation.
     */
    public function isModerate(): ?bool
    {
        return $this->moderate;
    }
}
