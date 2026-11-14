<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

class FieldServiceTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * {@inheritDoc}
     * @see \Illuminate\Foundation\Testing\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['models.field.name' => [
            'tax_form' => [
                'title' => 'Правовая форма (ФНС)',
                'bind' => \App\Handlers\Models\Field\Name\SelectName::class,
                'ui' => ['widget:select', 'screen:tax'],

                'tags' => [],
                'file_virtuals' => [],

                'options' => [
                    'values' => [
                        'tax_ru_phys' => ['title' => 'Физ. лицо'],
                        'tax_ru_jur' => ['title' => 'Юр. лицо'],
                        'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                    ],
                ],
            ],

            'tax_ru_fns_code' => [
                'title' => 'Код налоговой',
                'bind' => \App\Handlers\Models\Field\Name\RegexpName::class,
                'ui' => ['widget:regexp', 'screen:tax'],

                'tags' => ['tax_form:tax_ru_phys', 'tax_form:tax_ru_jur', 'tax_form:tax_ru_individual'],
                'file_virtuals' => [],

                'options' => [
                    'regexp' => '/^[0-9]{4}$/',
                ],
            ],

            'tax_ru_inn' => [
                'title' => 'ИНН',
                'bind' => \App\Handlers\Models\Field\Name\RuInnName::class,
                'ui' => ['widget:ru_inn', 'screen:tax'],

                'tags' => ['tax_form:tax_ru_phys', 'tax_form:tax_ru_jur', 'tax_form:tax_ru_individual'],
                'file_virtuals' => [],

                'options' => null,
            ],

            'tax_ru_kpp' => [
                'title' => 'КПП',
                'bind' => \App\Handlers\Models\Field\Name\RuKppName::class,
                'ui' => ['widget:ru_kpp', 'screen:tax'],

                'tags' => ['tax_form:tax_ru_jur'],
                'file_virtuals' => [],

                'options' => null,
            ],

            'tax_ru_registry' => [
                'title' => 'Выписка',
                'bind' => \App\Handlers\Models\Field\Name\NullableName::class,
                'ui' => ['widget:file', 'screen:tax'],

                'tags' => ['tax_form:tax_ru_jur'],
                'file_virtuals' => ['attachment'],

                'options' => null,
            ],

            'tax_ru_doc_*' => [
                'title' => 'Документ',
                'bind' => \App\Handlers\Models\Field\Name\NullableName::class,
                'ui' => ['widget:file', 'screen:tax'],

                'wildcard' => ['scan', 'photo'],
                'tags' => ['tax_form:tax_ru_never'],
                'file_virtuals' => ['attachment'],

                'options' => null,
            ],
        ]]);

        \App\Services\FieldService::expandConfig();
    }

    /**
     * @return void
     */
    public function test_expand_config()
    {
        $wildcardDetails = [
            'title' => 'Документ',
            'bind' => \App\Handlers\Models\Field\Name\NullableName::class,
            'ui' => ['widget:file', 'screen:tax'],

            'wildcard' => ['scan', 'photo'],
            'tags' => ['tax_form:tax_ru_never'],
            'file_virtuals' => ['attachment'],

            'options' => null,
        ];

        $this->assertSame(
            [
                'tax_form' => [
                    'title' => 'Правовая форма (ФНС)',
                    'bind' => \App\Handlers\Models\Field\Name\SelectName::class,
                    'ui' => ['widget:select', 'screen:tax'],

                    'tags' => [],
                    'file_virtuals' => [],

                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                ],

                'tax_ru_fns_code' => [
                    'title' => 'Код налоговой',
                    'bind' => \App\Handlers\Models\Field\Name\RegexpName::class,
                    'ui' => ['widget:regexp', 'screen:tax'],

                    'tags' => ['tax_form:tax_ru_phys', 'tax_form:tax_ru_jur', 'tax_form:tax_ru_individual'],
                    'file_virtuals' => [],

                    'options' => [
                        'regexp' => '/^[0-9]{4}$/',
                    ],
                ],

                'tax_ru_inn' => [
                    'title' => 'ИНН',
                    'bind' => \App\Handlers\Models\Field\Name\RuInnName::class,
                    'ui' => ['widget:ru_inn', 'screen:tax'],

                    'tags' => ['tax_form:tax_ru_phys', 'tax_form:tax_ru_jur', 'tax_form:tax_ru_individual'],
                    'file_virtuals' => [],

                    'options' => null,
                ],

                'tax_ru_kpp' => [
                    'title' => 'КПП',
                    'bind' => \App\Handlers\Models\Field\Name\RuKppName::class,
                    'ui' => ['widget:ru_kpp', 'screen:tax'],

                    'tags' => ['tax_form:tax_ru_jur'],
                    'file_virtuals' => [],

                    'options' => null,
                ],

                'tax_ru_registry' => [
                    'title' => 'Выписка',
                    'bind' => \App\Handlers\Models\Field\Name\NullableName::class,
                    'ui' => ['widget:file', 'screen:tax'],

                    'tags' => ['tax_form:tax_ru_jur'],
                    'file_virtuals' => ['attachment'],

                    'options' => null,
                ],

                'tax_ru_doc_scan' => $wildcardDetails,
                'tax_ru_doc_photo' => $wildcardDetails,
            ],
            config('models.field.name')
        );
    }

    /**
     * @return void
     */
    public function test_fields()
    {
        \Date::setTestNow('2025-10-02 10:00:00');
        $fieldService = \App::make(\App\Services\FieldService::class);
        $user = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $fields = [];

        // Iteration 1a
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => null,
                ],
            ],
            $fieldService->fields($user)
        );


        // Iteration 1b
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => null,
                ],
            ],
            $fieldService->fields($user, false)
        );


        // Iteration 2a
        $fields['tax_form'] = $this->saveField($user, ['name' => 'tax_form', 'data' => ['value' => 'tax_ru_phys']]);
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => $fields['tax_form'],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],
            ],
            $fieldService->fields($user)
        );


        // Iteration 2b
        $this->assertEquals(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => \App\Field::light()->where('user_id', '=', $user->id)->where('name', '=', 'tax_form')->first(),
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],
            ],
            $fieldService->fields($user, false)
        );


        // Iteration 3
        $fields['tax_ru_fns_code'] = $this->saveField($user, ['name' => 'tax_ru_fns_code', 'data' => ['value' => '1234']]);
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => $fields['tax_form'],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => $fields['tax_ru_fns_code'],
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],
            ],
            $fieldService->fields($user)
        );


        // Iteration 4
        $fields['tax_form'] = $this->saveField($user, ['name' => 'tax_form', 'data' => ['value' => 'tax_ru_jur']]);
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => $fields['tax_form'],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => $fields['tax_ru_fns_code'],
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_kpp',
                    'title' => 'КПП',
                    'ui' => ['widget:ru_kpp', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_registry',
                    'title' => 'Выписка',
                    'ui' => ['widget:file', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],
            ],
            $fieldService->fields($user)
        );


        // Iteration 5
        $this->deleteField($user, 'tax_ru_fns_code');
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => $fields['tax_form'],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_kpp',
                    'title' => 'КПП',
                    'ui' => ['widget:ru_kpp', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_registry',
                    'title' => 'Выписка',
                    'ui' => ['widget:file', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],
            ],
            $fieldService->fields($user)
        );


        // Iteration 6
        $fields['tax_ru_registry'] = $this->saveField($user, ['name' => 'tax_ru_registry', 'data' => null], 'attachment');
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => $fields['tax_form'],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_kpp',
                    'title' => 'КПП',
                    'ui' => ['widget:ru_kpp', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_registry',
                    'title' => 'Выписка',
                    'ui' => ['widget:file', 'screen:tax'],
                    'options' => null,
                    'field' => $fields['tax_ru_registry'],
                ],
            ],
            $fieldService->fields($user)
        );


        // Iteration 7
        $fields['tax_form'] = $this->saveField($user, ['name' => 'tax_form', 'data' => ['value' => 'tax_ru_individual']]);
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => $fields['tax_form'],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],
            ],
            $fieldService->fields($user)
        );


        // Iteration 8
        $fields['tax_form'] = $this->saveField($user, ['name' => 'tax_form', 'data' => ['value' => 'tax_ru_jur']]);
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => $fields['tax_form'],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'title' => 'Код налоговой',
                    'ui' => ['widget:regexp', 'screen:tax'],
                    'options' => ['regexp' => '/^[0-9]{4}$/'],
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_inn',
                    'title' => 'ИНН',
                    'ui' => ['widget:ru_inn', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_kpp',
                    'title' => 'КПП',
                    'ui' => ['widget:ru_kpp', 'screen:tax'],
                    'options' => null,
                    'field' => null,
                ],

                [
                    'name' => 'tax_ru_registry',
                    'title' => 'Выписка',
                    'ui' => ['widget:file', 'screen:tax'],
                    'options' => null,
                    'field' => $fields['tax_ru_registry'],
                ],
            ],
            $fieldService->fields($user)
        );

        $this->assertSame(
            [
                'tax_form' => 'Юр. лицо',
                'tax_ru_registry' => '',
            ],
            $fieldService->fieldsRaw($user)
        );


        // Iteration 9
        $this->deleteField($user, 'tax_form');
        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'title' => 'Правовая форма (ФНС)',
                    'ui' => ['widget:select', 'screen:tax'],
                    'options' => [
                        'values' => [
                            'tax_ru_phys' => ['title' => 'Физ. лицо'],
                            'tax_ru_jur' => ['title' => 'Юр. лицо'],
                            'tax_ru_individual' => ['title' => 'Индивидуальный предприниматель'],
                        ],
                    ],
                    'field' => null,
                ],
            ],
            $fieldService->fields($user)
        );

        $this->assertSame([], $fieldService->fieldsRaw($user));
    }

    /**
     * @param \App\User $user
     * @param array $data
     * @param string|null $fileVirtual
     * @return array
     */
    private function saveField(\App\User $user, array $data, ?string $fileVirtual = null): array
    {
        $data = array_replace(['user_id' => $user->id], $data);

        $field = \App\Field::where('user_id', '=', $data['user_id'])->where('name', '=', $data['name'])->first();
        if ($field) {
            $field->forceFill($data);
        } else {
            $field = \App\Field::factory()->make($data);
        }
        $field->validate()->save();

        if ($fileVirtual) {
            $this->createFile($field, $fileVirtual);
        }

        \App::make(\App\Services\FieldService::class)->flushCache($user->id);
        return \App\Field::light()->find($field->id)->toArray();
    }

    /**
     * @param \App\User $user
     * @param string $name
     * @return void
     */
    private function deleteField(\App\User $user, string $name): void
    {
        \App\Field::where('user_id', '=', $user->id)->where('name', '=', $name)->first()->delete();
        \App::make(\App\Services\FieldService::class)->flushCache($user->id);
    }
}
