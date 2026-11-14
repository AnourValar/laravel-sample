<?php

return [
    'attributes' => [
        'id' => 'ID',
        'pid' => 'Родительский пользователь',
        'role' => 'Роль',
        'role_data' => 'Анкета',
        'email' => 'Email',
        'phone' => 'Номер телефона',
        'password' => 'Пароль',
        'totp_secret' => 'TOTP сессия',
        'additional_abilities' => 'Дополнительные права',
            'additional_abilities.*' => 'Дополнительные права',
        'first_name' => 'Имя',
        'last_name' => 'Фамилия',
        'middle_name' => 'Отчество',
        'gender' => 'Пол',
        'birthday' => 'Дата рождения',
        'telegram_username' => 'Имя в телеграме',
        'telegram_chatid' => 'ID чата с ботом в телеграме',
        'tag_ids' => 'Теги пользователя',
            'tag_ids.*' => 'Тег пользователя',
        'comments' => 'Примечания',
        'search' => 'Поиск',
        'remember_token' => 'Токен сессии',
        'created_at' => 'Дата добавления',
        'updated_at' => 'Дата изменения',
        'deleted_at' => 'Дата удаления',
    ],

    'pid_not_exists' => 'Несуществующий родительский пользователь.',
    'pid_not_active' => 'Родительский пользователь удалён.',
    'additional_abilities_incorrect' => 'Несуществующие права.',
    'role_factors_not_enough' => 'Необходимо заполнить не менее :qty факторов.',
    'role_incorrect_transition' => 'Недопустимый переход роли пользователя.',
    'telegram_username_not_found' => 'Перед тем как сохранить имя в телеграме, необходимо написать боту - https://t.me/:bot?start=hello',
    'tag_ids_not_exists' => 'Несуществующие теги пользователя.',

    'cannot_be_deleted_has_childs' => 'Пользователь с активными дочерними пользователями не может быть удалён.',
    'cannot_be_restore_has_not_parent' => 'Пользователь с удалённым родительским пользователем не может быть восстановлен.',

    'role' => [
        'admin' => 'Администратор',
        'staff' => 'Сотрудник',
        'client' => 'Клиент',
    ],

    /*'role_handler' => [

    ],*/

    'additional_abilities' => [
        'admin_user_read' => 'Пользователи: просмотр',
        'admin_user_create' => 'Пользователи: создание (включая права [!])',
        'admin_user_update' => 'Пользователи: изменение (включая права [!])',
        'admin_user_delete' => 'Пользователи: удаление',
        'admin_user_login_as' => 'Пользователи: войти под другим',

        'admin_field_update' => 'Поля: изменение',

        'admin_ticket_read' => 'Тикеты: просмотр',
        'admin_ticket_update' => 'Тикеты: изменение',

        'admin_directory_read' => 'Справочники: просмотр',
        'admin_directory_create' => 'Справочники: создание',
        'admin_directory_update' => 'Справочники: изменение',
        'admin_directory_delete' => 'Справочники: удаление',

        'admin_setting_read' => 'Настройки: просмотр',
        'admin_setting_update' => 'Настройки: изменение',
    ],

    'gender' => [
        'male' => 'Мужской',
        'female' => 'Женский',
    ],

    'first_name_placeholder' => 'пользователь',
];
