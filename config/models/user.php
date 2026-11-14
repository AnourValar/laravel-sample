<?php

return [
    'role' => [
        'admin' => [
            'bind' => App\Handlers\Models\User\Role\AdminRole::class,
            'title' => 'models/user.role.admin',
            'route' => 'admin.index',

            'abilities' => [
                'admin', // Админцентр
                'admin.administration', // Администрирование
                'admin.user.read', 'admin.user.create', 'admin.user.update',
                'admin.user.delete', 'admin.user.login_as', // Пользователи
                'admin.field.update', // Поля
                'admin.ticket.read', 'admin.ticket.update', // Тикеты
                'admin.directory.read', 'admin.directory.create', 'admin.directory.update', 'admin.directory.delete', // Справочники
                'admin.setting.read', 'admin.setting.update', // Настройки

                'cabinet', // ЛК
                'cabinet.user.read', 'cabinet.user.create', 'cabinet.user.update', 'cabinet.user.delete', 'cabinet.user.notify', // Пользователи
                'cabinet.field.read', 'cabinet.field.update', // Поля
                'cabinet.directory.read', 'cabinet.directory.create', 'cabinet.directory.update', 'cabinet.directory.delete', // Справочники
                'cabinet.ticket.read', 'cabinet.ticket.create', 'cabinet.ticket.update', // Тикеты
            ],

            'factors_min' => 2, // минимальное кол-во факторов
            'rules' => [ // правила валидации
                'pid' => ['nullable', 'prohibited'],
                'email' => ['required'],
                'password' => ['required'],
                'first_name' => ['required'],
            ],
            'transitions' => ['staff'], // допустимые переходы роли

            'via_console' => true, // создание через консольную команду
            'is_staff' => true, // сотрудник (внутренняя роль)
            //'has_childs' => true, // допустимы дочерние пользователи
        ],

        'staff' => [
            'bind' => App\Handlers\Models\User\Role\AdminRole::class,
            'title' => 'models/user.role.staff',
            'route' => 'admin.index',

            'abilities' => [
                'admin', // Админцентр
                'cabinet', // ЛК
            ],

            'factors_min' => 2,
            'rules' => [
                'pid' => ['nullable', 'prohibited'],
                'email' => ['required'],
                'password' => ['required'],
                'first_name' => ['required'],
            ],
            'transitions' => ['admin'],

            'is_staff' => true,
        ],

        'client' => [
            'bind' => App\Handlers\Models\User\Role\ClientRole::class,
            'title' => 'models/user.role.client',
            'route' => null,

            'abilities' => [
                'cabinet', // ЛК
            ],

            'factors_min' => 2,
            'rules' => [
                'pid' => ['nullable', 'prohibited'],
                //'email' => ['required'],
                'phone' => ['required_without:email'],
                'password' => ['required'],
                'additional_abilities' => ['nullable', 'prohibited'],
            ],
            'transitions' => [],

            'is_customer' => true, // клиентская роль (публичная)
        ],
    ],

    'additional_abilities' => [
        'admin.user.read' => ['title' => 'models/user.additional_abilities.admin_user_read'],
        'admin.user.create' => ['title' => 'models/user.additional_abilities.admin_user_create'],
        'admin.user.update' => ['title' => 'models/user.additional_abilities.admin_user_update'],
        'admin.user.delete' => ['title' => 'models/user.additional_abilities.admin_user_delete'],
        'admin.user.login_as' => ['title' => 'models/user.additional_abilities.admin_user_login_as'],

        'admin.field.update' => ['title' => 'models/user.additional_abilities.admin_field_update'],

        'admin.ticket.read' => ['title' => 'models/user.additional_abilities.admin_ticket_read'],
        'admin.ticket.update' => ['title' => 'models/user.additional_abilities.admin_ticket_update'],

        'admin.directory.read' => ['title' => 'models/user.additional_abilities.admin_directory_read'],
        'admin.directory.create' => ['title' => 'models/user.additional_abilities.admin_directory_create'],
        'admin.directory.update' => ['title' => 'models/user.additional_abilities.admin_directory_update'],
        'admin.directory.delete' => ['title' => 'models/user.additional_abilities.admin_directory_delete'],

        'admin.setting.read' => ['title' => 'models/user.additional_abilities.admin_setting_read'],
        'admin.setting.update' => ['title' => 'models/user.additional_abilities.admin_setting_update'],
    ],

    'gender' => [
        'male' => ['title' => 'models/user.gender.male'],
        'female' => ['title' => 'models/user.gender.female'],
    ],

    'locale' => [
        'ru' => ['title' => 'Русский', 'icon' => 'flag-icon-ru'],
        //'en' => ['title' => 'English', 'icon' => 'flag-icon-us'],
    ],

    'notification_light' => ['id', 'type', 'data', 'read_at', 'created_at'],
    'notification_heavy' => ['id', 'type', 'data', 'read_at', 'created_at'],

    'token_casts' => [],
    'token_light' => ['id', 'name', 'abilities', 'ip_address', 'geo_location', 'last_used_at', 'created_at'],
    'token_heavy' => ['id', 'name', 'abilities', 'ip_address', 'geo_location', 'last_used_at', 'created_at'],
];
