<?php

namespace App;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Setting extends Model
{
    use \AnourValar\EloquentValidation\ModelTrait;

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
        'name', 'value',
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
        'updated_at',
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
        'name' => 'string',
        'value' => 'json:unicode',
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
        'value' => [
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
        'name',
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
            'name' => ['required', 'string', 'min:2', 'max:40', 'config:models.setting.name', 'unique'],
            'value' => ['required', 'array'],
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
        // value
        if ($this->isDirty('name', 'value')) {
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
     * Light набор полей
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function scopeLight(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $builder
            ->select(['id', 'name', 'value']);
    }

    /**
     * @return \App\Handlers\Models\Setting\Name\NameInterface
     */
    public function getNameHandler(): \App\Handlers\Models\Setting\Name\NameInterface
    {
        return \App::make($this->name_details['bind']);
    }

    /**
     * Мутатор: value
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_string($this->name) && config("models.setting.name.{$this->name}")) {
                    $value = $this->getNameHandler()->normalize($value);
                }

                if (isset($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
                }

                return ['value' => $value];
            }
        );
    }

    /**
     * Виртуальный атрибут: name_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function nameDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("models.setting.name.{$this->name}"),
        );
    }
}
