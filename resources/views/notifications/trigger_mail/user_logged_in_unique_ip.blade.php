@inject('stringHelper', App\Helpers\StringHelper::class)
@component('mail::message')
  # @lang('Здравствуйте, :name!', ['name' => $user->first_name_placeholder])

  *@lang('Выполнен вход с нового IP адреса.')*

  @lang('IP адрес'): **{{ $journal->ip_address ?? '-' }}**

  @component('mail::button', ['url' => config('app.url_cabinet'), 'color' => 'primary'])
    @lang('Личный кабинет')
  @endcomponent

  @lang('С уважением,')<br>{{ config('app.name') }}
@endcomponent
