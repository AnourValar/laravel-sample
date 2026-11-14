<div class="card card-light">
  <div class="card-header text-secondary">@lang('Обзор')</div>
  <div class="card-body">

    <div class="form-row">
      <table class="table table-sm">
        <tbody>
          <tr>
            <td style="width: 200px;">@lang('Дата создания')</td>
            <td><x-form::datetime :value="$user->created_at" /></td>
          </tr>

          <tr>
            <td>@lang('Пароль')</td>
            <td>
              @if ($user->password)
                @lang('Установлен')
              @else
                -
              @endif
            </td>
          </tr>

          <tr>
            <td>@lang('TOTP')</td>
            <td>
              @if ($user->totp_secret)
                @lang('Установлен')
              @else
                -
              @endif
              @if (($user->email || $user->phone) && Auth::user()->can('admin.user.update'))
                &nbsp;
                <input
                  type="submit"
                  form="form-ajax"
                  formaction="{{ route('admin.user.totp_generate', $user) }}"
                  class="btn btn-sm btn-info ajax-form swal-confirm"
                  value="@lang('Сгенерировать')"
                />
                @if ($user->totp_secret && Auth::user()->can('admin.user.update'))
                  <input
                    type="submit"
                    form="form-ajax"
                    formaction="{{ route('admin.user.totp_revoke', $user) }}"
                    class="btn btn-sm btn-danger ajax-form swal-confirm"
                    value="@lang('Отозвать')"
                  />
                @endif
              @endif
            </td>
          </tr>

          <tr>
            <td>@lang('Telegram')</td>
            <td>{{ $user->telegram_username ?? '-' }}</td>
          </tr>

          <tr class="{{ $user->deleted_at ? 'table-danger' : '' }}">
            <td>@lang('Дата удаления')</td>
            <td><x-form::datetime :value="$user->deleted_at" default="-" /></td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</div>
