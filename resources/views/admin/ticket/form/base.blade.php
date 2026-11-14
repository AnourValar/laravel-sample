<form method="POST" action="{{ route('admin.ticket.update', $ticket) }}" class="ajax-form {{ Auth::user()->can('admin.ticket.update') && !$ticket->closed_at ? '' : 'disable-all-always' }}">
  <div class="card card-light">
    <div class="card-header text-secondary">@lang('Обработка')</div>

    <div class="card-body">
      <h5 class="text-secondary">@lang('Прогресс')</h5>
      <div class="form-row">
        <div class="col-md-6">
          <label>@lang('Статус')</label>
          <x-select
            name="ticket[status]"
            options="models.ticket.status"
            :selected="$ticket->status"
            class="form-control select2bs4"
          />
        </div>

        <div class="col-md-6">
          <label>@lang('Модератор')</label>
          <x-select
            name="ticket[moderator_id]"
            class="form-control select2bs4-autocomplete"
            data-url="{{ route('admin.user.autocomplete', ['operation' => 'is_staff']) }}"
            :prepends="['' => '']"
            :options="$ticket->moderator_id ? [$ticket->moderator] : []"
            :selected="$ticket->moderator_id"
            :data-placeholder="__('Отсутсвует')"
          />
        </div>
      </div>

      <h5 class="text-secondary mt-4">@lang('Ответ')</h5>
      <div class="form-row">
        @if ($ticket->getTypeHandler() instanceof App\Handlers\Models\Ticket\Type\RenderInterface)
          {!! $ticket->getTypeHandler()->renderResponse($ticket, 'ticket[data_response]') !!}
        @else
          <div class="col-md-12 text-secondary font-italic">&lt; @lang('Редактирование не поддерживается') &gt;</div>
        @endif
      </div>
    </div>

    <div class="card-footer">
      <input type="submit" class="btn btn-primary" value="@lang('Сохранить')" />
    </div>
  </div>
</form>
