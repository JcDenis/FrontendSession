<?php

declare(strict_types=1);

namespace Dotclear\Plugin\FrontendSession;

use Dotclear\App;
use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;

/**
 * @brief       FrontendSession module template connected page helper.
 * @ingroup     FrontendSession
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
class FrontendSessionProfil
{
    /**
     * @var     array<string, Component>   $groups
     **/
    private array $groups = [];

    public function __construct(
        public readonly string $id
    ) {
    }

    /**
     * @param   array<int, Component>   $items
     */
    public function addAction(string $action, string $title, array $items): void
    {
        $this->groups[$action] = (new Div($this->id . $action . 'group'))
            ->class('session-form')
            ->items([
                (new Text('h3', $title)),
                (new Form($this->id . $action . 'form'))
                    ->action('')
                    ->method('post')
                    ->fields($items),
            ]);
    }

    public function getActions(): Set
    {
        return (new Set())->items($this->groups);
    }

    /**
     * @param   array<int, Component>   $items
     */
    public function getInputfield(array $items, bool $required = false): Div
    {
        $class = ['inputfield'];
        if ($required) {
            $class[] = 'required';
        }

        return (new Div())
            ->class($class)
            ->items($items);
    }

    /**
     * @param   array<int, Component>   $items
     */
    public function getControlset(string $action, string $title, array $items = []): Div
    {
        return (new Div())
            ->class('controlset')
            ->items([
                (new Submit($this->id . $action . 'save', Html::escapeHTML($title))),
                (new Hidden([$this->id . 'redir'], App::blog()->url() . App::url()->getBase($this->id))),
                (new Hidden([$this->id . 'state'], '')),
                (new Hidden([$this->id . 'check'], App::nonce()->getNonce())),
                (new Hidden([$this->id . 'action'], $action)),
                ... $items,
            ]);
    }
}
