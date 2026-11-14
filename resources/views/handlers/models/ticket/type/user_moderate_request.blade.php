@php
  $fields = \App::make(\App\Services\FieldService::class)->fields($ticket->author, false);
  $whiteList = $ticket->type_details['options']['fields'];

  $fields = array_filter($fields, fn(array $item) => in_array($item['name'], $whiteList));
@endphp

<x-admin.user.fields :user="$ticket->author" :fields="$fields" group="primary" />
