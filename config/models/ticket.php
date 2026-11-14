<?php

return [
    'type' => [
        'user_moderate' => [
            'title' => 'models/ticket.type.user_moderate',
            'bind' => App\Handlers\Models\Ticket\Type\UserModerateType::class,

            'rules' => [
                'author_id' => ['required'],
            ],
            'is_singleton' => true, // не более одного открытого тикета (для одного автора)
            'is_manual' => true, // "ручное" создание

            'options' => [
                'fields' => [
                    'profile_form', 'profile_full_name', 'profile_company', 'profile_ru_inn', 'profile_ru_kpp',
                    'profile_passport_document',
                ],
            ],
        ],

        'user_request' => [
            'title' => 'models/ticket.type.user_request',
            'bind' => App\Handlers\Models\Ticket\Type\SimpleType::class,

            'rules' => [
                'author_id' => ['required'],
            ],
            'is_manual' => true,

            'options' => [],
        ],

        'guest_request' => [
            'title' => 'models/ticket.type.guest_request',
            'bind' => App\Handlers\Models\Ticket\Type\GuestRequestType::class,

            'rules' => [
                'author_id' => ['nullable', 'prohibited'],
            ],
            'is_manual' => true,

            'options' => [],
        ],
    ],

    'status' => [
        'new' => [
            'title' => 'models/ticket.status.new',
            'ui' => 'success', // UI группа
            'flow' => null, // бизнес логика
        ],

        'in_work' => [
            'title' => 'models/ticket.status.in_work',
            'ui' => 'primary',
            'flow' => null,
        ],

        'approve' => [
            'title' => 'models/ticket.status.approve',
            'ui' => 'secondary',
            'flow' => 'finish_success',
        ],

        'reject' => [
            'title' => 'models/ticket.status.reject',
            'ui' => 'danger',
            'flow' => 'finish_failure',
        ],
    ],
];
