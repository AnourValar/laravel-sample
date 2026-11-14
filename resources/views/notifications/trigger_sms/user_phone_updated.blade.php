@inject('stringHelper', App\Helpers\StringHelper::class)
@lang('Ваш телефон изменён на :phone.', ['phone' => $stringHelper->formatPhone($user->phone) ?? '-'])
