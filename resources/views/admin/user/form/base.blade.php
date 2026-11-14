@inject('dateHelper', App\Helpers\DateHelper::class)
<form method="POST" action="{{ $user->exists ? route('admin.user.update', $user) : route('admin.user.create') }}" class="ajax-form">
  <div class="card card-light">
    <div class="card-header text-secondary">
      @lang('Карточка пользователя')
    </div>

    <div class="card-body">
      <h5 class="text-secondary">@lang('Аккаунт')</h5>
      <div class="form-row">
        <div class="col-md-4">
          <label class="sometimes-required">@lang('Email')</label>
          <input
            type="hidden"
            name="user[email]"
            value=""
            class="is-not-user"
          />
          <input
            type="hidden"
            name="user[additional_abilities]"
            value=""
            class="is-not-user"
          />
          <x-input
            type="email"
            name="user[email]"
            :value="$user->email"
            class="form-control is-user"
            placeholder="{{ __('Email') }}"
            autocomplete="new-email"
          />
        </div>

        <div class="col-md-4">
          <label class="sometimes-required">@lang('Телефон')</label>
          <x-input
            type="text"
            name="user[phone]"
            :value="$user->phone"
            class="form-control phone-mask"
            autocomplete="off"
          />
        </div>

        <div class="col-md-4">
          <label class="sometimes-required">@lang('Пароль')</label>
          <input
            type="hidden"
            name="user[password]"
            value=""
            class="is-not-user"
          />
          <x-input
            type="password"
            name="user[password]"
            class="form-control disable-if-empty is-user"
            placeholder="{{ __('Новый пароль') }}"
            autocomplete="new-password"
          />
        </div>
      </div>

      <div class="form-row">
        <div class="col-md-4">
          <label class="required">@lang('Роль')</label>
          <x-select
            name="user[role]"
            options="models.user.role"
            :prepends="['' => __('-- Не выбрана --')]"
            :selected="$user->role"
            class="form-control form-disable"
            data-render-url="{{ route('admin.user.render_role') }}"
            data-render-target="#user-role-data"
            data-render-attribute="user[role_data]"
            data-empty=".is-user"
            data-not-empty=".is-not-user"
            data-form-init="1"
            :data-mapping-empty="json_encode(ConfigHelper::keys('models.user.role', fn($value, $key) => !ConfigHelper::hasItem($value['rules']['email'] ?? [], 'prohibited')))"
          />
        </div>

        <div class="col-md-4">
          <label class="sometimes-required">@lang('Родительский пользователь')</label>
          <x-select
            name="user[pid]"
            class="form-control select2bs4-autocomplete"
            data-url="{{ route('admin.user.autocomplete', ['operation' => 'has_childs']) }}"
            :prepends="['' => '']"
            :options="$user->pid ? [$user->parent] : []"
            :selected="$user->pid"
            :data-placeholder="__('Отсутсвует')"
            :disabled="$user->exists"
          />
        </div>
      </div>

      <h5 class="text-secondary mt-4">@lang('Анкета')</h5>
      <div class="form-row">
        <div class="col-md-4">
          <label class="sometimes-required">@lang('Имя')</label>
          <x-input
            type="text"
            name="user[first_name]"
            :value="$user->first_name"
            class="form-control"
            placeholder="{{ __('Имя') }}"
            autocomplete="off"
          />
        </div>

        <div class="col-md-4">
          <label>@lang('Фамилия')</label>
          <x-input
            type="text"
            name="user[last_name]"
            :value="$user->last_name"
            class="form-control"
            placeholder="{{ __('Фамилия') }}"
            autocomplete="off"
          />
        </div>

        <div class="col-md-4">
          <label>@lang('Отчество')</label>
          <x-input
            type="text"
            name="user[middle_name]"
            :value="$user->middle_name"
            class="form-control"
            placeholder="{{ __('Отчество') }}"
            autocomplete="off"
          />
        </div>
      </div>

      <div class="form-row">
        <div class="col-md-4">
          <label>@lang('Пол')</label>
          <x-select
            name="user[gender]"
            options="models.user.gender"
            :prepends="['' => __('-- Не выбран --')]"
            :selected="$user->gender"
            class="form-control"
          />
        </div>

        <div class="col-md-4">
          <label>@lang('Дата рождения')</label>
          <x-input
            type="text"
            name="user[birthday]"
            :value="$dateHelper->formatDate($user->birthday)"
            class="form-control date-picker"
            autocomplete="off"
          />
        </div>
      </div>

      <h5 class="text-secondary mt-3">@lang('Взаимодействие')</h5>
      <div class="form-row">
        <div class="col-md-8">
          @lang('Примечания')
          <x-textarea
            name="user[comments]"
            class="form-control"
            :value="$user->comments"
            style="height: 148px;"
          />
        </div>

        @if ($user->exists)
          <div class="col-md-4">
            <label>@lang('Теги пользователя')</label>
            <x-select
              name="user[tag_ids][]"
              :options="$userTags"
              :selected="$user->tag_ids"
              :mapping="['value' => 'id']"
              class="select2bs4 null-if-empty"
              multiple="multiple"
              :data-placeholder="__('Не выбраны')"
            />
          </div>
        @endif
      </div>
    </div>

    <div class="card-footer">
      @yield('button_primary')
      @yield('button_additional')
    </div>
  </div>
</form>
