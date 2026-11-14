<div class="card card-light">
  <div class="card-header text-secondary">@lang('Обзор')</div>
  <div class="card-body">
    <table class="table table-sm mb-0">
      <tbody>
        <tr class="text-bold">
          <td style="width: 130px;">@lang('Тип')</td>
          <td>@lang($ticket->type_details['title'])</td>
        </tr>

        <tr>
          <td>@lang('Автор')</td>
          <td>
            <x-admin.user
              :value="$ticket->author"
              display="title"
              default="-"
              target="_blank"
            />
          </td>
        </tr>

        <tr>
          <td>@lang('Дата создания')</td>
          <td><x-form::datetime :value="$ticket->created_at" /></td>
        </tr>

        <tr class="{{ $ticket->closed_at ? 'table-light' : '' }}">
          <td>@lang('Дата закрытия')</td>
          <td><x-form::datetime :value="$ticket->closed_at" default="-" /></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
