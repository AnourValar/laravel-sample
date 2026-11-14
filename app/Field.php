<?php

namespace App;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Field extends Model
{
    use \AnourValar\EloquentValidation\ModelTrait;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'name', 'data', 'status',
    ];

    /**
     * '',[] => null convert
     *
     * @var array
     */
    protected $nullable = [
        'data',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'name' => 'string',
        'data' => 'encrypted:array',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
        'data' => [
            'jsonb' => true,
            'nullable' => ['*'],
            'purges' => ['*'],
            'types' => [],
            'sorts' => [],
            'lists' => [],
        ],
    ];

    /**
     * Calculated columns
     *
     * @var array
     */
    protected $computed = [

    ];

    /**
     * Immutable columns
     *
     * @var array
     */
    protected $unchangeable = [
        'user_id', 'name',
    ];

    /**
     * Unique columns sets
     *
     * @var array
     */
    protected $unique = [
        ['user_id', 'name'],
    ];

    /**
     * Get the validation rules
     *
     * @return array
     */
    public function saveRules()
    {
        return [
            'user_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'config:models.field.name'],
            'data' => ['nullable', 'array'],
            'status' => ['required', 'string', 'config:models.field.status'],
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
        // user_id
        $roles = \ConfigHelper::keys('models.user.role', ['is_customer' => true]);
        if ($this->isDirty('user_id') && ! \App\User::withTrashed()->whereIn('role', $roles)->find($this->user_id)) {
            $validator->errors()->add('user_id', trans('models/field.user_id_not_exists'));
        }

        // name
        // Целесообразность валидации "name" с точки зрения его доступности с текущим набором тегов - отсутствует

        // data
        if ($this->isDirty('name', 'data')) {
            $this->getNameHandler()->validate($this, $validator);
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

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\User::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function fileVirtuals(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\FileVirtual::class, 'entity', 'entity')->orderBy('id', 'ASC');
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
            ->orderBy('id', 'ASC');
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
            ->with(['fileVirtuals' => fn ($query) => $query->light()])
            ->select(['id', 'user_id', 'name', 'data', 'status', 'created_at'])
            ->publishFields(['data', 'status', 'created_at', 'fileVirtuals']);
    }

    /**
     * Heavy набор полей
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function scopeHeavy(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $builder->light();
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

        if (! $user->can('cabinet.field.read')) {
            $builder->where('user_id', '=', $user->id);
        }
    }

    /**
     * @return \App\Handlers\Models\Field\Name\NameInterface
     */
    public function getNameHandler(): \App\Handlers\Models\Field\Name\NameInterface
    {
        return \App::make($this->name_details['bind']);
    }

    /**
     * Виртуальный атрибут: name_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function nameDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("models.field.name.{$this->name}"),
        );
    }

    /**
     * Виртуальный атрибут: name_title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function nameTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trans($this->name_details['title']),
        );
    }

    /**
     * Виртуальный атрибут: status_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function statusDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("models.field.status.{$this->status}"),
        );
    }

    /**
     * Виртуальный атрибут: status_title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function statusTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trans($this->status_details['title']),
        );
    }
}
