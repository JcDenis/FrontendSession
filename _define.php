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
    '0.29',
    [
        'requires'    => [['core', '2.34']],
        'settings'    => ['blog' => '#params.' . $this->id . '_params'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-06-09T15:03:54+00:00',
    ]
);
