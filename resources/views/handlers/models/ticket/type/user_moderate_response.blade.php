@php
  $fields = \App::make(\App\Services\FieldService::class)->fields($ticket->author, false);
  $whiteList = $ticket->type_details['options']['fields'];

  $fields = array_filter($fields, fn(array $item) => in_array($item['name'], $whiteList));
@endphp

@foreach ($ticket->type_details['options']['fields'] as $field)
  @continue(!array_filter($fields, fn(array $item) => $item['name'] == $field) && empty($ticket->data_response['fields'][$field]))
  <div class="col-md-6 mb-2">
    <label class="mb-0">@lang(config("models.field.name.{$field}.title"))</label>
    <x-input
      type="text"
      name="{{ $name }}[fields][{{ $field }}]"
      class="form-control"
      :value="$ticket->data_response['fields'][$field] ?? null"
      :placeholder="__('Без ошибок')"
      autocomplete="off"
    />
  </div>
@endforeach
