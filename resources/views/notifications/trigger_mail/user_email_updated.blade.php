@component('mail::message')
  # @lang('Здравствуйте, :name!', ['name' => $user->first_name_placeholder])

  *@lang('Ваш Email успешно изменён.')*

  @lang('Новый Email'): **{{ $user->email ?? '-' }}**

  @component('mail::button', ['url' => config('app.url_cabinet'), 'color' => 'primary'])
    @lang('Личный кабинет')
  @endcomponent

  @lang('С уважением,')<br>{{ config('app.name') }}
@endcomponent
