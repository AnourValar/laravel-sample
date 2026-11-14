@props(['user', 'fields', 'group' => null])

<div {{ $attributes->merge(['class' => 'card card-' . ($group ?? 'light')]) }}>
  <div class="card-header {{ $group ? '' : 'text-secondary' }}">@lang('Поля')</div>
  <div class="card-body p-0">
    <table class="table table-striped table-empty">
      <tbody>
        @foreach ($fields as $field)
          <tr style="height: 60px;">
            <td colspan="2">
              <div class="d-inline-block text-bold pt-2">{{ $field['title'] }}</div>
              @if ($field['field'])
                <div class="float-right">
                  <form method="POST" action="{{ route('admin.field.update', ['user' => $user, 'field' => $field['field']]) }}" class="ajax-form d-flex">
                    <div class="mt-1 mr-2" id="panel-{{ $field['name'] }}" style="display: none;">
                      <input type="submit" class="btn btn-sm btn-outline-primary" value="@lang('Сохранить')" />
                      &nbsp;
                      <i class="fa-solid fa-triangle-exclamation text-lg text-warning" data-toggle="tooltip" title="@lang('Изменения не сохранены')"></i>
                    </div>

                    <div>
                      <x-select
                        name="field[status]"
                        class="form-control form-hide"
                        options="models.field.status"
                        :selected="$field['field']->status"
                        :disabled="!Auth::user()->can('admin.field.update')"
                        data-not-empty="#panel-{{ $field['name'] }}"
                        :data-mapping-empty="json_encode([$field['field']->status])"
                      />
                    </div>
                  </form>
                </div>
              @endif
            </td>
          </tr>

          @if ($field['field'])
            <tr>
              <td style="width: 10px;"></td>
              <td>{!! $field['field']->getNameHandler()->renderShow($field['field']) !!}</td>
            </tr>
          @else
            <tr>
              <td colspan="2" class="text-center font-italic text-secondary">&lt; @lang('Не заполнено') &gt;</td>
            </tr>
          @endif
        @endforeach
      </tbody>
    </table>
  </div>
</div>
