@inject('stringHelper', App\Helpers\StringHelper::class)
**@lang('Ваш телефон изменён')**

@lang('Новый телефон:') `{{ $stringHelper->formatPhone($user->phone) ?? '-' }}`

[@lang('Личный кабинет')]({{ config('app.url_cabinet') }})
