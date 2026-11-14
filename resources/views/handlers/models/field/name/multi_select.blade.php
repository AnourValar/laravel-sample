@php
  $options = $field->name_details['options']['values'];
@endphp

@foreach ($field->data['values'] as $value)
  {{ trans($options[$value]['title']) }}{{ $loop->last ? '' : ', ' }}
@endforeach

@includeWhen(!$raw, 'handlers.models.field.name.common.file_virtuals')
