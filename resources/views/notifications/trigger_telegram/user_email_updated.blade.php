**@lang('Ваш email изменён')**

@lang('Новый email:') `{{ $user->email ?? '-' }}`

[@lang('Личный кабинет')]({{ config('app.url_cabinet') }})
