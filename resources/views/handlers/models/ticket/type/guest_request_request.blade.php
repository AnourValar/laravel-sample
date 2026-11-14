@inject('stringHelper', App\Helpers\StringHelper::class)
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">@lang('Запрос')</h3>
  </div>
  <div class="card-body">
    <div class="form-row">
      <div class="col-md-4 form-group">
        <label class="text-secondary mb-1">@lang('Email')</label>
        <div>{{ $ticket->data_request['email'] ?? '-' }}</div>
      </div>

      <div class="col-md-4 form-group">
        <label class="text-secondary mb-1">@lang('Телефон')</label>
        <div>{{ $stringHelper->formatPhone($ticket->data_request['phone'] ?? null) ?? '-' }}</div>
      </div>

      <div class="col-md-4 form-group">
        <label class="text-secondary mb-1">@lang('ФИО')</label>
        <div>{{ $stringHelper->nameFull($ticket->data_request['last_name'] ?? null, $ticket->data_request['first_name'] ?? null, $ticket->data_request['middle_name'] ?? null) }}</div>
      </div>
    </div>

    <div class="form-row">
      <div class="col-md-12 form-group mb-0">
        <label class="text-secondary mb-1">@lang('Сообщение')</label>
        <div style="white-space: pre-line;">{{ $ticket->data_request['message'] ?? '-' }}</div>
      </div>
    </div>
  </div>
</div>
