<?php

namespace App;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Directory extends Model
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
        'type', 'data',
    ];

    /**
     * '',[] => null convert
     *
     * @var array
     */
    protected $nullable = [

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'search', 'updated_at', 'pivot',
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
        'type' => 'string',
        'user_id' => 'integer',
        'data' => 'json:unicode',
        'search' => 'string',
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
            'types' => [
                '$.title' => 'string',
                '$.color' => 'string',
                '$.description' => 'string',
            ],
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
        'search',
    ];

    /**
     * Immutable columns
     *
     * @var array
     */
    protected $unchangeable = [
        'type', 'user_id',
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
            'type' => ['required', 'string', 'config:models.directory.type'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'data' => ['required', 'array'],
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
        // type
        $validator->addRules(config("models.directory.type.{$this->type}.rules"));

        // type
        if (config("models.directory.type.{$this->type}.is_singleton")) {
            $this->handleUnique([['user_id', 'type']], $validator);
        }

        // user_id
        $roles = \ConfigHelper::keys('models.user.role', ['is_customer' => true]);
        if ($this->isDirty('user_id') && $this->user_id && ! \App\User::withTrashed()->whereIn('role', $roles)->find($this->user_id)) {
            $validator->errors()->add('user_id', trans('models/directory.user_id_not_exists'));
        }

        // data
        if ($this->isDirty('type', 'data')) {
            $this->getTypeHandler()->validate($this, $validator);
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
            ->select(['id', 'type', 'user_id', 'data']);
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
            ->addSelect(['created_at']);
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

        if ($user?->can('cabinet.directory.read')) {
            return;
        }

        $builder->where(function ($query) use ($user) {
            if ($user?->can('cabinet')) {
                $query->orWhere('user_id', '=', $user->id);
            }

            $query->orWhereNull('user_id');
        });
    }

    /**
     * @return \App\Handlers\Models\Directory\Type\TypeInterface
     */
    public function getTypeHandler(): \App\Handlers\Models\Directory\Type\TypeInterface
    {
        return \App::make($this->type_details['bind']);
    }

    /**
     * Виртуальный атрибут: type_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function typeDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("models.directory.type.{$this->type}"),
        );
    }

    /**
     * Виртуальный атрибут: type_title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function typeTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trans($this->type_details['title']),
        );
    }
}
