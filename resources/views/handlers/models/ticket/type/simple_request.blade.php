<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">@lang('Запрос')</h3>
  </div>
  <div class="card-body">
    <div class="form-group">
      <label class="text-secondary mb-1">@lang('Сообщение')</label>
      <div style="white-space: pre-line;">{{ $ticket->data_request['message'] }}</div>
    </div>
  </div>
</div>
