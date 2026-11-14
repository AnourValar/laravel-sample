{{ trans($field->name_details['options']['values'][$field->data['value']]['title']) }}

@includeWhen(!$raw, 'handlers.models.field.name.common.file_virtuals')
