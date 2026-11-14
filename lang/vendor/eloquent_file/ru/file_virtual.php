<?php

return [
    'attributes' => [
        'id' => 'ID',
        'file_physical_id' => 'Файл',
        'entity' => 'Сущность',
        'entity_id' => 'ID сущности',
        'name' => 'Техн. название',
        'filename' => 'Имя',
        'title' => 'Описание',
        'weight' => 'Вес',
        'details' => 'Детали',
        'archived_at' => 'Дата архивации',
        'created_at' => 'Дата создания',
        'updated_at' => 'Дата изменения',
    ],

    'file_physical_id_not_exists' => 'Несуществующий файл.',
    'file_physical_id_incorrect_visibility' => 'Физический файл имеет недопустимую область.',
    'file_physical_id_incorrect_type' => 'Физический файл имеет недопустимый тип.',

    'entity' => [
        'field' => [
            'name' => [
                'attachment' => 'Вложение',
            ],
        ],

        'ticket' => [
            'name' => [
                'attachment_request' => 'Вложение (запрос)',
                'attachment_response' => 'Вложение (ответ)',
            ],
        ],

        /*'user' => [
            'name' => [
                'avatar' => 'Аватар',
            ],
        ],*/
    ],

    'entity_handler' => [
        'over_limit_qty' => 'Кол-во файлов ":name" не может превышать :limit.',
        'over_limit_size' => 'Размер файлов ":name" не может превышать :limit кб.',

        'field' => [
            'entity_id_not_exists' => 'Несуществующее поле.',
        ],

        'ticket' => [
            'entity_id_not_exists' => 'Несуществующий тикет.',
        ],

        /*'user' => [
            'entity_id_not_exists' => 'Несуществующий пользователь.',
        ],*/
    ],
];
