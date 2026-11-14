<div class="col-md-12">
  <label>@lang('Сообщение')</label>
  <x-textarea
    name="{{ $name }}[message]"
    class="form-control"
    :value="$ticket->data_response['message'] ?? null"
    rows="6"
  />
</div>
