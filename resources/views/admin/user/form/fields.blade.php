@php
  $fields = \App::make(\App\Services\FieldService::class)->fields($user, false);
@endphp

<x-admin.user.fields :user="$user" :fields="$fields" />
