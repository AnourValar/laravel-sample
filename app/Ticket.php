<?php

namespace App;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Ticket extends Model
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
        'type', 'data_request', 'data_response', 'status',
    ];

    /**
     * '',[] => null convert
     *
     * @var array
     */
    protected $nullable = [
        'author_id', 'moderator_id', 'data_request', 'data_response',
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
        'status' => 'new',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'author_id' => 'integer',
        'moderator_id' => 'integer',
        'type' => 'string',
        'data_request' => 'json:unicode',
        'data_response' => 'json:unicode',
        'status' => 'string',
        'closed_at' => 'datetime',
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
        'data_request' => [
            'jsonb' => true,
            'nullable' => ['*'],
            'purges' => ['*'],
            'types' => ['*' => 'string'],
            'sorts' => [],
            'lists' => [],
        ],

        'data_response' => [
            'jsonb' => true,
            'nullable' => ['*'],
            'purges' => ['*'],
            'types' => ['*' => 'string'],
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
        'closed_at',
    ];

    /**
     * Immutable columns
     *
     * @var array
     */
    protected $unchangeable = [
        'author_id', 'type', 'data_request',
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
            'author_id' => ['nullable', 'integer', 'min:1'],
            'moderator_id' => ['nullable', 'integer', 'min:1'],
            'type' => ['required', 'string', 'config:models.ticket.type'],
            'data_request' => ['nullable', 'array'],
            'data_response' => ['nullable', 'array'],
            'status' => ['required', 'string', 'config:models.ticket.status'],
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
        // author_id
        $roles = \ConfigHelper::keys('models.user.role', ['is_customer' => true]);
        if ($this->isDirty('author_id') && $this->author_id && ! \App\User::withTrashed()->whereIn('role', $roles)->find($this->author_id)) {
            $validator->errors()->add('author_id', trans('models/ticket.author_id_not_exists'));
        }

        // moderator_id
        $roles = \ConfigHelper::keys('models.user.role', ['is_staff' => true]);
        if ($this->isDirty('moderator_id') && $this->moderator_id && ! \App\User::withTrashed()->whereIn('role', $roles)->find($this->moderator_id)) {
            $validator->errors()->add('moderator_id', trans('models/ticket.moderator_id_not_exists'));
        }

        // type
        $validator->addRules(config("models.ticket.type.{$this->type}.rules"));

        // data_request, data_response
        if ($this->isDirty('type', 'data_request', 'data_response')) {
            $this->getTypeHandler()->validate($this, $validator);
        }

        // closed_at
        if ($this->closed_at) {
            $this->handleUnchangeable(['moderator_id', 'data_response', 'status'], $validator);
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
        throw new \LogicException('Incorrect usage.');
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
    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\User::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function moderator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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
            ->with([
                'author' => fn ($query) => $query->light(),
                'moderator' => fn ($query) => $query->light(),
                'fileVirtuals' => fn ($query) => $query->light(),
            ])
            ->select(['id', 'author_id', 'moderator_id', 'type', 'data_request', 'status', 'closed_at', 'created_at']);
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
            ->addSelect(['data_response']);
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

        if ($user->can('cabinet.ticket.read')) {
            return;
        }

        $builder->whereHas('author', function ($query) use ($user) {
            $query->where(function ($query) use ($user) {
                if ($user->can('cabinet')) {
                    $query->orWhere('id', '=', $user->id);
                }

                $query->orWhere('id', '=', 0);
            });
        });
    }

    /**
     * @return \App\Handlers\Models\Ticket\Type\TypeInterface
     */
    public function getTypeHandler(): \App\Handlers\Models\Ticket\Type\TypeInterface
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
            get: fn ($value) => config("models.ticket.type.{$this->type}"),
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

    /**
     * Виртуальный атрибут: status_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function statusDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("models.ticket.status.{$this->status}"),
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
