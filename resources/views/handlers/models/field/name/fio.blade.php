{{ $field->data['last_name'] }} {{ $field->data['first_name'] }} {{ $field->data['middle_name'] ?? '' }}

@includeWhen(!$raw, 'handlers.models.field.name.common.file_virtuals')
