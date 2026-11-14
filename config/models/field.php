<?php

return [ // synced with a ticket
    'name' => [
        /*
        |--------------------------------------------------------------------------
        | Профиль (KYC)
        |--------------------------------------------------------------------------
        */

        'profile_form' => [
            'title' => 'models/field.name.profile_form',
            'bind' => App\Handlers\Models\Field\Name\SelectName::class,
            //'wildcard' => [],
            'ui' => ['widget:select', 'screen:profile'], // UI группы

            'tags' => [], // теги, при которых поле становится доступным
            'file_virtuals' => [], // name, доступные к загрузке
            //'is_readonly' => true, // недоступен к прямому заполнению

            'options' => [
                'values' => [
                    'ru_phys' => ['title' => 'models/field.name_handler.select.profile_form.ru_phys'],
                    'ru_jur' => ['title' => 'models/field.name_handler.select.profile_form.ru_jur'],
                    'ru_individual' => ['title' => 'models/field.name_handler.select.profile_form.ru_individual'],
                ],
            ],
        ],

        'profile_full_name' => [
            'title' => 'models/field.name.profile_full_name',
            'bind' => App\Handlers\Models\Field\Name\FioName::class,
            //'wildcard' => [],
            'ui' => ['widget:fio', 'screen:profile'],

            'tags' => ['profile_form:ru_phys', 'profile_form:ru_individual'],
            'file_virtuals' => [],
            //'is_readonly' => true,

            'options' => null,
        ],

        'profile_company' => [
            'title' => 'models/field.name.profile_company',
            'bind' => App\Handlers\Models\Field\Name\TextName::class,
            //'wildcard' => [],
            'ui' => ['widget:text', 'screen:profile'],

            'tags' => ['profile_form:ru_jur'],
            'file_virtuals' => [],
            //'is_readonly' => true,

            'options' => [
                'min_length' => 2,
                'max_length' => 80,
                'faker' => 'company',
            ],
        ],

        'profile_ru_inn' => [
            'title' => 'models/field.name.profile_ru_inn',
            'bind' => App\Handlers\Models\Field\Name\RuInnName::class,
            //'wildcard' => [],
            'ui' => ['widget:ru_inn', 'screen:profile'],

            'tags' => ['profile_form:ru_phys', 'profile_form:ru_jur', 'profile_form:ru_individual'],
            'file_virtuals' => [],
            //'is_readonly' => true,

            'options' => null,
        ],

        'profile_ru_kpp' => [
            'title' => 'models/field.name.profile_ru_kpp',
            'bind' => App\Handlers\Models\Field\Name\RuKppName::class,
            //'wildcard' => [],
            'ui' => ['widget:ru_kpp', 'screen:profile'],

            'tags' => ['profile_form:ru_jur'],
            'file_virtuals' => [],
            //'is_readonly' => true,

            'options' => null,
        ],

        'profile_passport_document' => [
            'title' => 'models/field.name.profile_passport_document',
            'bind' => App\Handlers\Models\Field\Name\NullableName::class,
            //'wildcard' => [],
            'ui' => ['widget:file', 'screen:profile'],

            'tags' => ['profile_form:ru_phys', 'profile_form:ru_individual'],
            'file_virtuals' => ['attachment'],
            //'is_readonly' => true,

            'options' => null,
        ],
    ],

    'status' => [
        'draft' => [
            'title' => 'models/field.status.draft',
            'flow' => 'draft', // бизнес логика
        ],

        'moderation' => [
            'title' => 'models/field.status.moderation',
            'flow' => 'moderation',
        ],

        'verified' => [
            'title' => 'models/field.status.verified',
            'flow' => 'accept',
        ],
    ],
];
