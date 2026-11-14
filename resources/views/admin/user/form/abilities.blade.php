<form method="POST" action="{{ route('admin.user.update', $user) }}" class="ajax-form">
  <div class="card card-light">
    <div class="card-header text-secondary">
      <x-form::tooltip :title="__('Недоступные к редактированию права определены ролью.')">@lang('Список прав')</x-form::tooltip>
    </div>

    <div class="card-body">
      <input type="hidden" name="user[additional_abilities]" value="" />
      @forelse (config('models.user.additional_abilities') as $name => $details)
        <x-form::icheck
          name="user[additional_abilities][]"
          :value="$name"
          :checked="$user->can($name)"
          :disabled="in_array($name, $user->role_details['abilities'])"
        >
          {{ trans($details['title']) }}
        </x-form::icheck>
      @empty
        <span class="font-italic text-secondary">&lt; @lang('Недоступны') &gt;</span>
      @endforelse
    </div>

    <div class="card-footer">
      <input type="submit" class="btn btn-outline-primary btn-sm" value="@lang('Сохранить')" />
    </div>
  </div>
</form>
