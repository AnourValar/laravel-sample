<?php

return [
    'attributes' => [
        'id' => 'ID',
        'type' => 'Тип',
        'user_id' => 'Пользователь',
        'data' => 'Карточка',
        'search' => 'Поиск',
        'created_at' => 'Дата добавления',
        'updated_at' => 'Дата изменения',
    ],

    'user_id_not_exists' => 'Несуществующий пользователь.',

    'type' => [
        'user_tag' => 'Тег пользователя',
    ],

    'type_handler' => [
        'user_tag' => [
            'attributes' => [
                'data.title' => 'Название',
                'data.color' => 'Цвет',
                'data.description' => 'Описание',
            ],
        ],
    ],
];
