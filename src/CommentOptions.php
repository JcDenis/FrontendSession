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

    public function setActive(bool $value): self
    {
        $this->active = $value;

        return $this;
    }

    public function setModerate(bool $value): self
    {
        $this->moderate = $value;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function isModerate(): ?bool
    {
        return $this->moderate;
    }
}
