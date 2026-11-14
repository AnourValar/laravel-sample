<?php

return [
    'attributes' => [
        'id' => 'ID',
        'user_id' => 'Пользователь',
        'name' => 'Название поля',
        'data' => 'Значение поля',
        'status' => 'Статус',
        'created_at' => 'Дата добавления',
        'updated_at' => 'Дата изменения',
    ],

    'user_id_not_exists' => 'Несуществующий пользователь.',

    'name' => [
        // Профиль (KYC)
        'profile_form' => 'Правовая форма',
        'profile_full_name' => 'ФИО',
        'profile_company' => 'Название организации',
        'profile_ru_inn' => 'ИНН',
        'profile_ru_kpp' => 'КПП',
        'profile_passport_document' => 'Паспорт (скан)',
    ],

    'status' => [
        'draft' => 'Черновик',
        'moderation' => 'Модерация',
        'verified' => 'Верифицирован',
    ],

    'name_handler' => [
        'fio' => [
            'attributes' => [
                'data.first_name' => 'Имя',
                'data.last_name' => 'Фамилия',
                'data.middle_name' => 'Отчество',
            ],
        ],

        'date' => [
            'attributes' => [
                'data.value' => 'Дата',
            ],
        ],

        'regexp' => [
            'attributes' => [
                'data.value' => 'Значение',
            ],
        ],

        'ru_inn' => [
            'attributes' => [
                'data.value' => 'ИНН',
            ],
        ],

        'ru_kpp' => [
            'attributes' => [
                'data.value' => 'КПП',
            ],
        ],

        'select' => [
            'attributes' => [
                'data.value' => 'Значение из списка',
            ],

            'profile_form' => [
                'ru_phys' => 'Физ. лицо',
                'ru_jur' => 'Юр. лицо',
                'ru_individual' => 'Индивидуальный предприниматель',
            ],
        ],

        'multi_select' => [
            'attributes' => [
                'data.values' => 'Значения из списка',
                'data.values.*' => 'Значение из списка',
            ],
        ],

        'text' => [
            'attributes' => [
                'data.value' => 'Значение',
            ],
        ],

        'integer' => [
            'attributes' => [
                'data.value' => 'Значение',
            ],
        ],
    ],
];
