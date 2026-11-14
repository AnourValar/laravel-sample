<div class="card card-light">
  <div class="card-header text-secondary">@lang('Обзор')</div>
  <div class="card-body">
    <table class="table table-sm mb-0">
      <tbody>
        <tr class="text-bold">
          <td style="width: 130px;">@lang('Тип')</td>
          <td>{{ $directory->type_title }}</td>
        </tr>

        <tr>
          <td>@lang('Пользователь')</td>
          <td>
            <x-admin.user
              :value="$directory->user"
              display="title"
              default="-"
              target="_blank"
            />
          </td>
        </tr>

        <tr>
          <td>@lang('Дата создания')</td>
          <td><x-form::datetime :value="$directory->created_at" /></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
