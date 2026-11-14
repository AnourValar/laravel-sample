@inject('stringHelper', App\Helpers\StringHelper::class)
@lang('Новый телефон:') {{ $stringHelper->formatPhone($user->phone) ?? '-' }}.
