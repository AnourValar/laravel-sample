<?php

return [
    'attributes' => [
        'id' => 'ID',
        'author_id' => 'Автор',
        'moderator_id' => 'Модератор',
        'type' => 'Тип',
        'data_request' => 'Данные запроса',
        'data_response' => 'Данные ответа',
        'status' => 'Статус',
        'closed_at' => 'Дата закрытия',
        'created_at' => 'Дата добавления',
        'updated_at' => 'Дата изменения',

    ],

    'author_id_not_exists' => 'Несуществующий автор.',
    'moderator_id_not_exists' => 'Несуществующий модератор.',

    'type' => [
        'user_moderate' => 'Модерация пользователя',
        'user_request' => 'Пользовательский запрос',
        'guest_request' => 'Гостевой запрос',
    ],

    'status' => [
        'new' => 'Новый',
        'in_work' => 'В работе',
        'approve' => 'Выполнен',
        'reject' => 'Отклонён',
    ],

    'type_handler' => [
        'user_moderate' => [
            'attributes' => [
                'data_response.fields' => 'Ошибки полей',
                'data_response.fields.*' => 'Ошибка поля',
            ],
        ],

        'simple' => [
            'attributes' => [
                'data_request.message' => 'Сообщение (запрос)',
                'data_response.message' => 'Сообщение (ответ)',
            ],
        ],

        'guest_request' => [
            'attributes' => [
                'data_request.phone' => 'Телефон',
                'data_request.email' => 'Email',
                'data_request.message' => 'Сообщение',
                'data_request.first_name' => 'Имя',
                'data_request.last_name' => 'Фамилия',
                'data_request.middle_name' => 'Отчество',
            ],
        ],
    ],
];
