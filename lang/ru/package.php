<?php

/**
 * Данный пакет рендерится в admin.layout, auth.layout
 */

return [
    'table_empty' => 'Нет записей',
    'ajax_error' => 'Произошла ошибка :(',
    'ajax_form' => 'Дублирование',

    'select2' => [
        'language' => 'ru',
    ],

    'datetime_picker' => [
        'locale' => 'ru',
        'format' => 'd.m.Y H:i',
        'format_date' => 'd.m.Y',
        'dayOfWeekStart' => 1,
    ],

    'month_picker' => [
        'format' => 'yyyy-mm',
        'locale' => 'ru',
    ],

    /*'summernote' => [
        'lang' => 'ru-RU',
    ],*/

    'momentjs' => [
        'date' => 'DD.MM.YYYY',
        'datetime' => 'DD.MM.YYYY HH:mm',
    ],

    'swal' => [
        'title' => 'Вы уверены?',
        'text' => 'Действие нельзя будет отменить',
        'confirm' => 'Да',
        'delete' => 'Да, удалить!',
        'cancel' => 'Отмена',
    ],

    'inputmask' => [
        'radixPoint' => ',',
        'groupSeparator' => ' ',
    ],

    'phone' => [
        '7' => ['mask' => '+7(999) 999-9999'], // Россия & Казахстан
    ],
];
