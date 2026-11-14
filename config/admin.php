<?php

return [
    'menu' => [
        /*[
            'title' => 'admin.menu.index',
            'route' => 'admin.index',
            'user_ability' => ['admin'],
            'additional' => ['icon' => 'fa-house'],
        ],*/

        // Пользователи
        [
            'title' => 'admin.menu.user_group',
            'additional' => ['icon' => 'fa-user'],
            'dropdown' => [
                [
                    'title' => 'admin.menu.user_add',
                    'route' => 'admin.user.add',
                    'user_ability' => ['admin.user.create'],
                    'additional' => ['icon' => 'fa-plus'],
                ],
                [
                    'title' => 'admin.menu.user_index',
                    'route' => 'admin.user.index',
                    'user_ability' => ['admin.user.read'],
                    'additional' => ['icon' => 'fa-list'],
                ],
            ],
        ],

        // Тикеты
        [
            'title' => 'admin.menu.ticket',
            'route' => 'admin.ticket.index',
            'user_ability' => ['admin.ticket.read'],
            'additional' => ['icon' => 'fa-clipboard'],
        ],

        // Справочники
        [
            'title' => 'admin.menu.directory_group',
            'additional' => ['icon' => 'fa-book'],
            'dropdown' => [
                [
                    'title' => 'admin.menu.directory_add',
                    'route' => 'admin.directory.add',
                    'user_ability' => ['admin.directory.create'],
                    'additional' => ['icon' => 'fa-plus'],
                ],
                [
                    'title' => 'admin.menu.directory_index',
                    'route' => 'admin.directory.index',
                    'user_ability' => ['admin.directory.read'],
                    'additional' => ['icon' => 'fa-list'],
                ],
            ],
        ],

        // Настройки
        [
            'title' => 'admin.menu.setting',
            'route' => 'admin.setting.index',
            'user_ability' => ['admin.setting.read'],
            'additional' => ['icon' => 'fa-gear'],
        ],

        // Журналы
        [
            'title' => 'admin.menu.journal',
            'route' => 'admin.journal.index',
            'user_ability' => ['admin.administration'],
            'additional' => ['icon' => 'fa-history'],
        ],

        // Администрирование
        [
            'title' => 'admin.menu.administration_group',
            'additional' => ['icon' => 'fa-cogs'],
            'dropdown' => [
                [
                    'title' => 'admin.menu.administration_diagnostic',
                    'route' => 'admin.administration.diagnostic',
                    'user_ability' => ['admin.administration'],
                    'additional' => ['icon' => 'fa-wrench', 'target' => '_blank', 'query' => '?fresh'],
                ],
                [
                    'title' => 'admin.menu.administration_horizon',
                    'route' => 'horizon.index',
                    'user_ability' => ['admin.administration'],
                    'additional' => ['icon' => 'fa-list-check', 'target' => '_blank'],
                ],
                [
                    'title' => 'admin.menu.administration_pulse',
                    'route' => 'pulse',
                    'user_ability' => ['admin.administration'],
                    'additional' => ['icon' => 'fa-heartbeat', 'target' => '_blank'],
                ],
            ],
        ],
    ],
];
