<form
  method="POST"
  action="{{ route('admin.file.upload', ['entity' => 'ticket', 'entity_id' => $ticket->id, 'name' => 'attachment_response']) }}"
  enctype="multipart/form-data"
  class="ajax-form"
>
  <div class="card card-light {{ Auth::user()->can('admin.ticket.update') && !$ticket->closed_at ? '' : 'disable-all-always' }}">
    <div class="card-header text-secondary">@lang('Вложения')</div>
    <div class="card-body">

      <table class="table table-sm mb-3 table-empty">
        <thead>
          <tr>
            <th class="align-middle text-center" style="width: 80px;">@lang('Превью')</th>
            <th class="align-middle">@lang('Ссылка')</th>
            <th class="align-middle">@lang('Назначение')</th>
            <th class="align-middle text-center" style="width: 100px;">@lang('Удаление')</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($ticket->fileVirtuals as $fileVirtual)
            @php $fileVirtual->setRelation('entitable', $ticket); @endphp
            <tr>
              <td class="align-middle text-center"><x-form::file_preview :url="$fileVirtual->url_generate['preview'] ?? $fileVirtual->url" /></td>
              <td class="text-truncate align-middle">
                <a
                  href="{{ $fileVirtual->url_generate['preview'] ?? $fileVirtual->url }}"
                  class="text-info"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <i class="fa-solid fa-paperclip"></i> {{ $fileVirtual->title ?? $fileVirtual->filename }}
                </a>
              </td>
              <td class="align-middle">{{ $fileVirtual->name_title }}</td>
              <td class="align-middle text-center">
                @if ($fileVirtual->getEntityHandler()->canUpload($fileVirtual, Auth::user()))
                  <button
                    type="submit"
                    class="btn btn-sm btn-outline-danger swal-delete"
                    title="@lang('Удалить')"
                    formaction="{{ route('admin.file.delete', $fileVirtual) }}"
                  >
                    &nbsp;<i class="fa-solid fa-trash"></i>&nbsp;
                  </button>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      <div class="m-2 p-2 text-center">
        <div class="custom-file">
          <input
            type="file"
            class="custom-file-input form-disable"
            name="file"
            data-not-empty="#new-file-button"
          />
          <label class="custom-file-label">@lang('Файл не выбран')</label>
        </div>
        <div class="pt-2">
          <input
            type="submit"
            class="btn btn-outline-success"
            id="new-file-button"
            value="@lang('Загрузить')"
            disabled="disabled"
          />
        </div>
      </div>

    </div>
  </div>
</form>
