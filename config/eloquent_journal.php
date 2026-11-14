<?php

use AnourValar\EloquentJournal\Handlers\ModelType;

return [
    'model' => App\Journal::class,
    'web_layout' => 'admin.layout', // eloquent_journal::layout

    'entity' => [
        App\User::class => [ // Relation::morphMap() - must be set
            'title' => 'eloquent_journal::journal.entity.user',

            'schema' => [ // modification for the values
                'role' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.user.role', 'display' => 'title'],
                'additional_abilities.*' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.user.additional_abilities', 'display' => 'title'],
                'gender' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.user.gender', 'display' => 'title'],
            ],

            'attribute_names' => [ // modification for the keys
                'models/user.role_handler.{role}.attributes', // lang path
            ],

            'observe' => true, // log all changes
            'exclude_attributes' => [], // exclude from the log
        ],

        /*App\Directory::class => [
            'title' => 'eloquent_journal::journal.entity.directory',

            'schema' => [
                'type' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.directory.type', 'display' => 'title'],
                'user_id' => ['type' => ModelType::SCHEMA_MODEL, 'model' => App\User::class, 'display' => ['email', 'phone']],
            ],

            'attribute_names' => [
                'models/directory.type_handler.{type}.attributes',
            ],

            'observe' => true,
            'exclude_attributes' => [],
        ],*/

        App\Field::class => [
            'title' => 'eloquent_journal::journal.entity.field',

            'schema' => [
                'user_id' => ['type' => ModelType::SCHEMA_MODEL, 'model' => App\User::class, 'display' => ['email', 'phone']],
                'name' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.field.name', 'display' => 'title'],
                'status' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.field.status', 'display' => 'title'],
            ],

            'attribute_names' => [

            ],

            'observe' => true,
            'exclude_attributes' => [],
        ],

        App\Setting::class => [
            'title' => 'eloquent_journal::journal.entity.setting',

            'schema' => [
                'name' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.setting.name', 'display' => 'title'],
            ],

            'attribute_names' => [
                'models/setting.name_handler.{name}.attributes',
            ],

            'observe' => true,
            'exclude_attributes' => [],
        ],

        App\Ticket::class => [
            'title' => 'eloquent_journal::journal.entity.ticket',

            'schema' => [
                'author_id' => ['type' => ModelType::SCHEMA_MODEL, 'model' => App\User::class, 'display' => ['email', 'phone']],
                'moderator_id' => ['type' => ModelType::SCHEMA_MODEL, 'model' => App\User::class, 'display' => ['email', 'phone']],
                'type' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.ticket.type', 'display' => 'title'],
                'status' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.ticket.status', 'display' => 'title'],
            ],

            'attribute_names' => [
                'data_response.fields' => ['type' => ModelType::SCHEMA_CONFIG, 'config' => 'models.field.name', 'display' => 'title'],
                'models/ticket.type_handler.simple.attributes',
                'models/ticket.type_handler.{type}.attributes',
            ],

            'observe' => true,
            'exclude_attributes' => [],
        ],
    ],

    'type' => [
        'model' => [
            'bind' => ModelType::class,
            'title' => 'eloquent_journal::journal.type.model',
        ],

        'integration' => [
            'bind' => AnourValar\EloquentJournal\Handlers\IntegrationType::class,
            'title' => 'eloquent_journal::journal.type.integration',
        ],

        'metric' => [
            'bind' => AnourValar\EloquentJournal\Handlers\MetricType::class,
            'title' => 'eloquent_journal::journal.type.metric',
        ],
    ],

    'event' => [
        // Model
        'create' => ['title' => 'eloquent_journal::journal.event.create', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],
        'update' => ['title' => 'eloquent_journal::journal.event.update', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],
        'delete' => ['title' => 'eloquent_journal::journal.event.delete', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],
        'restore' => ['title' => 'eloquent_journal::journal.event.restore', 'optgroup' => 'eloquent_journal::journal.type.model', 'is_public' => false],

        // Integration
        //'mts_sms_send' => ['title' => 'eloquent_journal::journal.event.mts_sms_send', 'optgroup' => 'eloquent_journal::journal.type.integration', 'is_public' => false],

        // Metric
        'user_session_obtain' => ['title' => 'eloquent_journal::journal.event.user_session_obtain', 'optgroup' => 'eloquent_journal::journal.type.metric', 'is_public' => true],
        'user_token_obtain' => ['title' => 'eloquent_journal::journal.event.user_token_obtain', 'optgroup' => 'eloquent_journal::journal.type.metric', 'is_public' => true],
        'user_token_delete' => ['title' => 'eloquent_journal::journal.event.user_token_delete', 'optgroup' => 'eloquent_journal::journal.type.metric', 'is_public' => true],
    ],
];
