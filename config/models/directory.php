<?php

return [
    'type' => [
        'user_tag' => [
            'bind' => App\Handlers\Models\Directory\Type\UserTagType::class,
            'title' => 'models/directory.type.user_tag',

            'rules' => [ // правила валидации
                'user_id' => ['nullable', 'prohibited'],
            ],

            'is_manually' => true, // справочник изменяется через UI
            //'is_singleton' => true, // справочник присутствует в базе в единственном экземпляре
        ],
    ],

    'batch' => [ // attach / detach / set
        'user' => [
            'tag_ids' => ['nullable', 'not_empty', 'list', 'max:100'],
                'tag_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ],
    ],
];
