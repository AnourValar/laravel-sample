<?php

return [
    'attributes' => [
        'id' => 'ID',
        'user_id' => 'Пользователь',
        'trigger' => 'Триггер',
        'channels' => 'Каналы',
            'channels.*' => 'Канал',
        'created_at' => 'Дата создания',
        'updated_at' => 'Дата изменения',
    ],

    'user_id_not_exists' => 'Несуществующий пользователь.',
    'channels_not_exists' => 'Несуществующие каналы.',

    'trigger' => [
        'user_optgroup' => 'Профиль',
        'security_optgroup' => 'Безопасность',

        'user_email_updated' => 'Изменён email',
        'user_phone_updated' => 'Изменён телефон',
        'user_password_updated' => 'Изменён пароль',
        'personal_message' => 'Персональное уведомление',
        'user_logged_in_unique_ip' => 'Вход с нового IP',
    ],

    'trigger_mail' => [
        'user_email_updated' => 'Ваш Email изменён',
        'user_logged_in_unique_ip' => 'Вход с нового IP',
        'user_password_updated' => 'Ваш пароль изменён',
        'user_phone_updated' => 'Ваш телефон изменён',
    ],

    'trigger_push' => [
        'user_email_updated' => 'Ваш Email изменён',
        'user_logged_in_unique_ip' => 'Вход с нового IP',
        'user_password_updated' => 'Ваш пароль изменён',
        'user_phone_updated' => 'Ваш телефон изменён',
    ],

    'channels' => [
        'database' => 'ЛК',
        'mail' => 'Email',
        'sms' => 'SMS',
        'telegram' => 'Telegram',
        'push' => 'Push',
    ],
];
