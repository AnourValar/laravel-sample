@if ($ticket->getTypeHandler() instanceof App\Handlers\Models\Ticket\Type\RenderInterface)
  {!! $ticket->getTypeHandler()->renderRequest($ticket) !!}
@else
  <div class="card card-primary">
    <div class="card-header">
      <h3 class="card-title">@lang('Запрос (RAW)')</h3>
    </div>
    <div class="card-body">
      <textarea class="form-control" rows="10" readonly>{{ json_encode($ticket->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</textarea>
    </div>
  </div>
@endif
