<?php

namespace App;

use AnourValar\EloquentNotification\Adapters\Telegram\TelegramInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use \AnourValar\EloquentValidation\Features\JsonRelations;
    use \AnourValar\EloquentValidation\ModelTrait;
    use HasApiTokens, HasFactory, Notifiable;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Trim columns
     *
     * @var array
     */
    protected $trim = [
        'role_data', 'email', 'first_name', 'last_name', 'middle_name', 'gender', 'telegram_username',
        'comments',
    ];

    /**
     * '',[] => null convert
     *
     * @var array
     */
    protected $nullable = [
        'pid', 'role_data', 'email', 'phone', 'password', 'totp_secret', 'additional_abilities', 'first_name',
        'last_name', 'middle_name', 'gender', 'birthday', 'telegram_username', 'tag_ids', 'comments',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password', 'totp_secret', 'telegram_chatid', 'search', 'remember_token', 'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'pid' => 'integer',
        'role' => 'string',
        'role_data' => 'json:unicode',
        'email' => 'string',
        'phone' => 'string',
        'password' => 'string',
        'totp_secret' => 'string', // encrypted high-level
        'additional_abilities' => 'json:unicode',
        'first_name' => 'string',
        'last_name' => 'string',
        'middle_name' => 'string',
        'gender' => 'string',
        'birthday' => 'date:Y-m-d',
        'telegram_username' => 'string',
        'telegram_chatid' => 'string',
        'tag_ids' => 'json:unicode',
        'comments' => 'string',
        'search' => 'string',
        'remember_token' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Mutators for nested JSON.
     * jsonb - sort an array by key
     * nullable - '',[] => null convert (nested)
     * purges - remove null elements (nested)
     * types - set the type of value (nested)
     * sorts - sort an array (nested)
     * lists - drop array keys (nested)
     *
     * @var array
     */
    protected $jsonNested = [
        'role_data' => [
            'jsonb' => true,
            'nullable' => ['*'],
            'purges' => ['*'],
            'types' => [],
            'sorts' => [],
            'lists' => [],
        ],

        'tag_ids' => [
            'jsonb' => true,
            'nullable' => ['*'],
            'purges' => ['*'],
            'types' => ['$.*' => 'integer'],
            'sorts' => ['*'],
            'lists' => ['*'],
        ],
    ];

    /**
     * Calculated columns
     *
     * @var array
     */
    protected $computed = [
        'telegram_chatid', 'search', 'remember_token',
    ];

    /**
     * Immutable columns
     *
     * @var array
     */
    protected $unchangeable = [
        'pid',
    ];

    /**
     * Unique columns sets
     *
     * @var array
     */
    protected $unique = [

    ];

    /**
     * Get the validation rules
     *
     * @return array
     */
    public function saveRules()
    {
        return [
            'pid' => ['nullable', 'integer', 'min:1', 'not_in:' . ((int) $this->id)],
            'role' => ['required', 'string', 'config:models.user.role'],
            'role_data' => ['nullable', 'array'],
            'email' => ['nullable', 'string', 'max:70', 'email:filter', 'unique'],
            'phone' => ['nullable', 'string', 'max:20', 'phone', 'unique'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'totp_secret' => ['nullable', 'string'], // no deep validation
            'additional_abilities' => ['nullable', 'array', 'list'],
                 'additional_abilities.*' => ['required', 'string', 'max:100', 'distinct'],
            'first_name' => ['nullable', 'string', 'min:2', 'max:60'],
            'last_name' => ['nullable', 'string', 'min:2', 'max:60'],
            'middle_name' => ['nullable', 'string', 'min:2', 'max:60'],
            'gender' => ['nullable', 'string', 'config:models.user.gender'],
            'birthday' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'telegram_username' => ['nullable', 'string', 'min:2', 'max:50'],
            'tag_ids' => ['nullable', 'list', 'max:10'],
                'tag_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'comments' => ['nullable', 'string', 'min:1', 'max:500'],
        ];
    }

    /**
     * "Save" after-validation
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param bool $basic
     * @return void
     */
    public function saveAfterValidation(\Illuminate\Validation\Validator $validator, bool $basic): void
    {
        // pid
        if ($this->isDirty('pid') && $this->pid) {
            $check = \App\User::query()
                ->withTrashed()
                ->whereIn('role', \ConfigHelper::keys('models.user.role', ['has_childs' => true]))
                ->find($this->pid);

            if (! $check) {
                $validator->errors()->add('pid', trans('models/user.pid_not_exists'));
            }

            if ($check?->deleted_at && ! $this->deleted_at) {
                $validator->errors()->add('pid', trans('models/user.pid_not_active'));
            }
        }

        // role
        $validator->addRules(config("models.user.role.{$this->role}.rules"));

        // role
        if ($this->isDirty('role') && $this->exists) {
            if (! in_array($this->role, config('models.user.role.'.$this->getOriginal('role').'.transitions'))) {
                $validator->errors()->add('role', trans('models/user.role_incorrect_transition'));
            }
        }

        // role
        if (
            isset($this->role_details['factors_min'])
            && ! \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->faAtLeast($this->role_details['factors_min'], $this)
        ) {
            $validator->errors()->add('role', trans('models/user.role_factors_not_enough', ['qty' => $this->role_details['factors_min']]));
        }

        // role_data
        if ($this->isDirty('role', 'role_data')) {
            $this->getRoleHandler()->validate($this, $validator);
        }

        // additional_abilities
        if ($this->additional_abilities) {
            $allAbilities = array_merge(\ConfigHelper::keys('models.user.additional_abilities'), $this->role_details['abilities']);
            if (array_diff($this->additional_abilities, $allAbilities)) {
                $validator->errors()->add('additional_abilities', trans('models/user.additional_abilities_incorrect'));
            }
        }

        // telegram_username
        if ($this->isDirty('telegram_username') && $this->telegram_username) {
            if (! \App::make(TelegramInterface::class)->fetchChatId($this->telegram_username)) {
                $botName = config('notification.bindings.'.TelegramInterface::class.'.name');
                $validator->errors()->add('telegram_username', trans('models/user.telegram_username_not_found', ['bot' => $botName]));
            }
        }

        // tag_ids
        if ($this->isDirty('tag_ids') && $this->tag_ids) {
            $check = \App\Directory::query()
                ->where('type', '=', 'user_tag')
                ->whereNull('user_id')
                ->whereIn('id', $this->tag_ids)
                ->count();

            if ($check != count($this->tag_ids)) {
                $validator->errors()->add('tag_ids', trans('models/user.tag_ids_not_exists'));
            }
        }
    }

    /**
     * "Delete" after-validation
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param bool $basic
     * @return void
     */
    public function deleteAfterValidation(\Illuminate\Validation\Validator $validator, bool $basic): void
    {
        // Активные дочерние пользователи?
        if (\App\User::where('pid', '=', $this->id)->first()) {
            $validator->errors()->add('id', trans('models/user.cannot_be_deleted_has_childs'));
        }
    }

    /**
     * "Restore" after-validation
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param bool $basic
     * @return void
     */
    public function restoreAfterValidation(\Illuminate\Validation\Validator $validator, bool $basic): void
    {
        // Удалённый родительский пользователь?
        if ($this->pid && \App\User::onlyTrashed()->where('id', '=', $this->pid)->first()) {
            $validator->errors()->add('id', trans('models/user.cannot_be_restore_has_not_parent'));
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(self::class, 'pid')->withTrashed();
    }

    /**
     * @return \AnourValar\EloquentValidation\Features\Relations\BelongsToJson
     */
    public function directories(): \AnourValar\EloquentValidation\Features\Relations\BelongsToJson
    {
        return $this->belongsToJson(\App\Directory::class, 'tag_ids'); // не использовать в has сценариях
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function journals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Journal::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userNotifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\UserNotification::class);
    }

    /**
     * Сортировка
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function scopeSort(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $builder
            ->orderBy('deleted_at', 'DESC')
            ->orderBy('id', 'DESC');
    }

    /**
     * Light набор полей
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function scopeLight(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $builder
            ->select([
                'id', 'pid', 'role', 'email', 'phone', 'first_name', 'last_name', 'middle_name', 'tag_ids',
                'created_at', 'deleted_at',
            ]);
    }

    /**
     * Heavy набор полей
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function scopeHeavy(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $builder
            ->light()
            ->addSelect(['role_data', 'gender', 'birthday', 'telegram_username', 'additional_abilities']);
    }

    /**
     * ACL
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \App\User|null $user
     * @return void
     */
    public function scopeAcl(\Illuminate\Database\Eloquent\Builder $builder, ?\App\User $user = null): void
    {
        if (! $user) {
            $user = \Auth::user();
        }

        if (! $user->can('cabinet.user.read')) {
            $builder->where('id', '=', $user->id);
        }
    }

    /**
     * Login
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $login
     * @return void
     */
    public function scopeLogin(\Illuminate\Database\Eloquent\Builder $builder, string $login): void
    {
        $login = mb_strtolower(trim($login));
        $isEmail = strpos($login, '@') !== false;

        $builder
            ->whereIn('role', \ConfigHelper::keys('models.user.role', ['abilities' => 'cabinet']))
            ->when($isEmail, fn ($query) => $query->where('email', '=', $login))
            ->when(! $isEmail, fn ($query) => $query->where('phone', '=', $login));
    }

    /**
     * Autocomplete
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param array|null $roleRules
     * @return void
     */
    public function scopeToAutocomplete(\Illuminate\Database\Eloquent\Builder $builder, ?array $roleRules = null): void
    {
        if ($roleRules) {
            $builder->whereIn('role', \ConfigHelper::keys('models.user.role', $roleRules));
        }

        $builder
            ->orderBy('role', 'ASC')
            ->orderBy('id', 'ASC')
            ->select(['id', 'role', 'email', 'first_name', 'last_name', 'middle_name', 'deleted_at']);
    }

    /**
     * @return \App\Handlers\Models\User\Role\RoleInterface
     */
    public function getRoleHandler(): \App\Handlers\Models\User\Role\RoleInterface
    {
        return \App::make(config('models.user.role')[$this->role]['bind']);
    }

    /**
     * Мутатор: email
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_string($value)) {
                    $value = mb_strtolower($value);
                }

                return ['email' => $value];
            }
        );
    }

    /**
     * Мутатор: phone
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_string($value)) {
                    $value = \App::make(\App\Helpers\StringHelper::class)->canonizePhone($value);
                }

                return ['phone' => $value];
            }
        );
    }

    /**
     * Виртуальный атрибут: role_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function roleDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("models.user.role.{$this->role}"),
        );
    }

    /**
     * Виртуальный атрибут: role_title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function roleTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trans(config('models.user.role')[$this->role]['title']),
        );
    }

    /**
     * Виртуальный атрибут: phone_format
     *
     * @return Attribute
     */
    protected function phoneFormat(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return \App::make(\App\Helpers\StringHelper::class)->formatPhone($attributes['phone'] ?? null);
            },
        );
    }

    /**
     * Виртуальный атрибут: first_name_placeholder
     *
     * @return Attribute
     */
    protected function firstNamePlaceholder(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name ?? trans('models/user.first_name_placeholder'),
        );
    }

    /**
     * Виртуальный атрибут: title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function title(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $data = [
                    $this->last_name,
                    $this->first_name,
                    $this->middle_name,
                ];

                return implode(' ', array_diff($data, [null]));
            }
        );
    }

    /**
     * Route notifications for the email channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|null
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    /**
     * Route notifications for the Telegram channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|null
     */
    public function routeNotificationForTelegram($notification)
    {
        return $this->telegram_chatid;
    }

    /**
     * Route notifications for the SMS channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|null
     */
    public function routeNotificationForSms($notification)
    {
        return $this->phone;
    }

    /**
     * Route notifications for the Push channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string|array|null
     */
    public function routeNotificationForPush($notification)
    {
        return $this->tokens()->orderBy('id', 'DESC')->limit(10)->pluck('push_token')->toArray();
    }
}
