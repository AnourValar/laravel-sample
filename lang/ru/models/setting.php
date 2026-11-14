<?php

return [
    'attributes' => [
        'id' => 'ID',
        'name' => 'Настройка',
        'value' => 'Значение',
        'created_at' => 'Дата добавления',
        'updated_at' => 'Дата изменения',
    ],

    'name' => [
        'user_demo' => 'Демо пользователь',
    ],

    'name_handler' => [
        'user_demo' => [
            'attributes' => [
                'value.phone' => 'Телефон',
                'value.code' => 'СМС Код',
                'value.enabled' => 'Включён',
            ],
        ],
    ],
];
