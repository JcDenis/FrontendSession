<?php
/**
 * @file
 * @brief       The plugin FrontendSession definition
 * @ingroup     FrontendSession
 *
 * @defgroup    FrontendSession Plugin cinecturlink2.
 *
 * Allow session on frontend.
 *
 * @author      Jean-Christian Paul Denis
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

$this->registerModule(
    'Frontend sessions',
    'Allow session on frontend.',
    'Jean-Christian Paul Denis and Contributors',
    '0.2',
    [
        'requires'    => [['core', '2.33']],
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://git.dotclear.watch/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://git.dotclear.watch/JcDenis/' . $this->id . '/src/branch/master/README.md',
        'repository'  => 'https://git.dotclear.watch/JcDenis/' . $this->id . '/raw/branch/master/dcstore.xml',
    ]
);
