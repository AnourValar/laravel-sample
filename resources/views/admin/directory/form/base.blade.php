@php
  $canUpdate = $directory->getTypeHandler() instanceof App\Handlers\Models\Directory\Type\RenderInterface && Auth::user()->can('admin.directory.update');
  $canDelete = Auth::user()->can('admin.directory.delete');
@endphp

<form method="POST" action="{{ route('admin.directory.update', $directory) }}" class="ajax-form">
  <div class="card card-light">
    <div class="card-header text-secondary">@lang('Карточка')</div>

    <div class="card-body {{ $canUpdate ? '' : 'disable-all-always' }}">
      @if ($directory->getTypeHandler() instanceof App\Handlers\Models\Directory\Type\RenderInterface)
        {!! $directory->getTypeHandler()->renderEdit($directory, 'directory[data]') !!}
      @else
        <div class="form-row">
          <div class="col-md-12 text-secondary font-italic">
            &lt; @lang('Редактирование не поддерживается') &gt;
            <pre class="mt-2">{{ json_encode($directory->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) }}</pre>
          </div>
        </div>
      @endif
    </div>

    <div class="card-footer">
      <span class=" {{ $canUpdate ? '' : 'disable-all-always' }}">
        <input type="submit" class="btn btn-primary" value="@lang('Сохранить')" />
      </span>
      <span class=" {{ $canDelete ? '' : 'disable-all-always' }}">
        <input
          type="submit"
          class="btn btn-danger swal-delete"
          formaction="{{ route('admin.directory.delete', $directory) }}"
          value="@lang('Удалить!')"
        />
      </span>
    </div>
  </div>
</form>
