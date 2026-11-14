<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FieldTest extends TestCase
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
                'bind' => \App\Handlers\Models\Field\Name\RegexpName::class,
                'ui' => ['widget:ru_inn', 'screen:tax'],

                'tags' => ['tax_form:tax_ru_phys', 'tax_form:tax_ru_jur', 'tax_form:tax_ru_individual'],
                'file_virtuals' => [],

                'options' => [
                    'regexp' => '/^[0-9]{4,20}$/',
                ],
            ],

            'tax_ru_kpp' => [
                'title' => 'КПП',
                'bind' => \App\Handlers\Models\Field\Name\RegexpName::class,
                'ui' => ['widget:ru_kpp', 'screen:tax'],

                'tags' => ['tax_form:tax_ru_jur'],
                'file_virtuals' => [],

                'options' => [
                    'regexp' => '/^[0-9]{4,20}$/',
                ],
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
                'tags' => ['tax_form:tax_ru_jur'],
                'file_virtuals' => ['attachment'],

                'options' => null,
            ],
        ]]);

        \App\Services\FieldService::expandConfig();
    }

    /**
     * @return void
     */
    public function test_index_user_success()
    {
        $user = $this->authUser('cabinet');
        $fieldService = \App::make(\App\Services\FieldService::class);

        // Iteration 1
        $this->get('/api/field/index/user/' . $user->id)->assertStatus(200)->assertExactJson($fieldService->fields($user));

        // Iteration 2 (cache)
        foreach (\ConfigHelper::keys('models.field.name') as $name) {
            if (mt_rand(0, 1)) {
                \App\Field::factory()->create(['name' => $name, 'user_id' => $user->id]);
            }
        }
        $this->get('/api/field/index/user/' . $user->id)->assertStatus(200)->assertExactJson($fieldService->fields($user));
    }

    /**
     * @return void
     */
    public function test_index_user_failure_not_auth()
    {
        $this
            ->get('/api/field/index/user/1')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_index_user_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->get('/api/field/index/user/' . $user->id)->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_index_user_failure_acl_2()
    {
        $user1 = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        config(["models.user.role.{$user1->role}.abilities" => ['cabinet']]);

        $this->get('/api/field/index/user/' . $user1->id)->assertStatus(200);
        $this->get('/api/field/index/user/' . $user2->id)->assertStatus(403);
        $this->get('/api/field/index/user/' . ($user2->id + 100))->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_save_user_success()
    {
        \Date::setTestNow('2025-10-01 11:00:00');
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $fields = \App::make(\App\Services\FieldService::class)->fieldsRaw($user);


        // Iteration 1
        $this
            ->post(
                '/api/field/tax_form/save/user/' . $user->id,
                ['field' => ['data' => ['value' => 'tax_ru_phys']]]
            )
            ->assertStatus(200)
            ->assertJson(['field' => ['id' => true]]);

        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'data' => ['value' => 'tax_ru_phys'],
                    'status' => 'draft',
                    'created_at' => '2025-10-01T11:00:00.000000Z',
                    'file_virtuals' => [],
                ],
            ],
            \App\Field::light()->sort()->where('user_id', '=', $user->id)->addPublishFields(['name'])->get()->toArray()
        );


        // Iteration 2
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(false);
        \Date::setTestNow('2025-10-01 12:00:00');
        $this
            ->post(
                '/api/field/tax_ru_fns_code/save/user/' . $user->id,
                ['field' => ['user_id' => 1, 'data' => ['value' => '1234'], 'status' => 'verified']]
            )
            ->assertStatus(200)
            ->assertJson(['field' => ['id' => true]]);

        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'data' => ['value' => 'tax_ru_phys'],
                    'status' => 'draft',
                    'created_at' => '2025-10-01T11:00:00.000000Z',
                    'file_virtuals' => [],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'data' => ['value' => '1234'],
                    'status' => 'draft',
                    'created_at' => '2025-10-01T12:00:00.000000Z',
                    'file_virtuals' => [],
                ],
            ],
            \App\Field::light()->sort()->where('user_id', '=', $user->id)->addPublishFields(['name'])->get()->toArray()
        );
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);


        // Iteration 3
        \Date::setTestNow('2025-10-01 12:00:00');
        $this
            ->post(
                '/api/field/tax_ru_fns_code/save/user/' . $user->id,
                ['field' => ['data' => ['value' => '5678']]]
            )
            ->assertStatus(200)
            ->assertJson(['field' => ['id' => true]]);

        $this->assertSame(
            [
                [
                    'name' => 'tax_form',
                    'data' => ['value' => 'tax_ru_phys'],
                    'status' => 'draft',
                    'created_at' => '2025-10-01T11:00:00.000000Z',
                    'file_virtuals' => [],
                ],

                [
                    'name' => 'tax_ru_fns_code',
                    'data' => ['value' => '5678'],
                    'status' => 'draft',
                    'created_at' => '2025-10-01T12:00:00.000000Z',
                    'file_virtuals' => [],
                ],
            ],
            \App\Field::light()->sort()->where('user_id', '=', $user->id)->addPublishFields(['name'])->get()->toArray()
        );

        $this->assertNotSame($fields, \App::make(\App\Services\FieldService::class)->fieldsRaw($user));
    }

    /**
     * @return void
     */
    public function test_save_user_failure_not_auth()
    {
        $this
            ->post('/api/field/tax_form/save/user/1', [])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_save_user_failure_validation()
    {
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form']);
        $fieldId = (int) \App\Field::max('id');

        $this
            ->post('/api/field/tax_form/save/user/' . $user->id, ['field' => ['data' => ['value' => 'foo']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['field.tax_form.data.value' => true]]);

        $this
            ->post('/api/field/tax_ru_fns_code/save/user/' . $user->id, ['field' => ['data' => null]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['field.tax_ru_fns_code.data.value' => true]]);

        $this->assertSame($fieldId, (int) \App\Field::max('id'));
    }

    /**
     * @return void
     */
    public function test_save_user_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $fieldId = (int) \App\Field::max('id');

        $this->post('/api/field/tax_form/save/user/' . $user->id, ['field' => ['foo' => 'bar']])->assertStatus(403);

        $this->assertSame($fieldId, (int) \App\Field::max('id'));
    }

    /**
     * @return void
     */
    public function test_save_user_failure_acl_2()
    {
        $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $fieldId = (int) \App\Field::max('id');

        $this->post('/api/field/tax_form/save/user/' . $user2->id, ['field' => ['data' => ['value' => 'tax_ru_phys']]])->assertStatus(403);
        $this->post('/api/field/tax_form/save/user/' . ($user2->id + 100), ['field' => ['data' => ['value' => 'tax_ru_phys']]])->assertStatus(422);

        $this->assertSame($fieldId, (int) \App\Field::max('id'));
    }

    /**
     * @return void
     */
    public function test_save_user_failure_acl_3()
    {
        $user = $this->authUser('cabinet');

        $field = \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form', 'data' => ['value' => 'tax_ru_jur'], 'status' => 'moderation']);
        $this->post('/api/field/tax_form/save/user/' . $user->id, ['field' => ['data' => ['value' => 'tax_ru_phys']]])->assertStatus(403);

        $this->assertSame(['value' => 'tax_ru_jur'], $field->refresh()->data);
    }

    /**
     * @return void
     */
    public function test_upload_user_success()
    {
        $this->fakeStorages();
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form', 'data' => ['value' => 'tax_ru_jur']]);
        $fields = \App::make(\App\Services\FieldService::class)->fieldsRaw($user);


        // Iteration 1
        $response = $this
            ->post(
                '/api/field/tax_ru_doc_scan/upload/user/' . $user->id,
                ['file' => new UploadedFile(storage_path('app/fake/pdf/01.pdf'), basename('01.pdf'), 'application/pdf', null, true)]
            )
            ->assertStatus(200);

        $fileVirtual = \App\FileVirtual::latest('id')->first();
        $response->assertExactJson(['file_virtual' => ['id' => $fileVirtual->id]]);

        $this->assertSame(
            [
                'entity' => 'field',
                'entity_id' => $fieldId = $fileVirtual->entity_id,
                'name' => 'attachment',
                'filename' => '01.pdf',
                'mime_type' => 'application/pdf',
                'title' => null,
                'weight' => 0,
                'details' => null,
            ],
            $fileVirtual->load('filePhysical')->only('entity', 'entity_id', 'name', 'filename', 'mime_type', 'title', 'weight', 'details')
        );


        // Iteration 2
        $response = $this
            ->post(
                '/api/field/tax_ru_doc_scan/upload/user/' . $user->id,
                ['file' => new UploadedFile(storage_path('app/fake/pdf/02.pdf'), basename('02.pdf'), 'application/pdf', null, true)]
            )
            ->assertStatus(200);

        $fileVirtual = \App\FileVirtual::latest('id')->first();
        $response->assertExactJson(['file_virtual' => ['id' => $fileVirtual->id]]);

        $this->assertSame(
            [
                'entity' => 'field',
                'entity_id' => $fieldId,
                'name' => 'attachment',
                'filename' => '02.pdf',
                'mime_type' => 'application/pdf',
                'title' => null,
                'weight' => 0,
                'details' => null,
            ],
            $fileVirtual->load('filePhysical')->only('entity', 'entity_id', 'name', 'filename', 'mime_type', 'title', 'weight', 'details')
        );
        $this->assertNotSame($fields, \App::make(\App\Services\FieldService::class)->fieldsRaw($user));
    }

    /**
     * @return void
     */
    public function test_upload_user_failure_not_auth()
    {
        $this->post('/api/field/tax_ru_doc_scan/upload/user/1', [])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_upload_user_failure_validation()
    {
        $this->fakeStorages();
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);

        // Unsupported file
        $this
            ->post(
                '/api/field/tax_ru_doc_scan/upload/user/' . $user->id,
                ['file' => new UploadedFile(storage_path('app/fake/01.foo'), '01.foo', null, null, true)]
            )
            ->assertStatus(422)
            ->assertJson(['message' => true, 'errors' => ['file' => true]]);

        // Missed file
        $this
            ->post('/api/field/tax_ru_doc_scan/upload/user/' . $user->id)
            ->assertStatus(422)
            ->assertJson(['message' => true, 'errors' => ['error' => true]]);
    }

    /**
     * @return void
     */
    public function test_upload_user_failure_acl_1()
    {
        $this->fakeStorages();
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $maxFileVirtualId = (int) \App\FileVirtual::max('id');

        $this
            ->post(
                '/api/field/tax_ru_doc_scan/upload/user/' . $user->id,
                ['file' => new UploadedFile(storage_path('app/fake/pdf/01.pdf'), basename('01.pdf'), 'application/pdf', null, true)]
            )
            ->assertStatus(403);

        $this->assertSame($maxFileVirtualId, (int) \App\FileVirtual::max('id'));
    }

    /**
     * @return void
     */
    public function test_upload_user_failure_acl_2()
    {
        $this->fakeStorages();
        $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $maxFileVirtualId = (int) \App\FileVirtual::max('id');

        $this
            ->post(
                '/api/field/tax_ru_doc_scan/upload/user/' . $user2->id,
                ['file' => new UploadedFile(storage_path('app/fake/pdf/01.pdf'), basename('01.pdf'), 'application/pdf', null, true)]
            )
            ->assertStatus(403);

        $this
            ->post(
                '/api/field/tax_ru_doc_scan/upload/user/' . ($user2->id + 100),
                ['file' => new UploadedFile(storage_path('app/fake/pdf/01.pdf'), basename('01.pdf'), 'application/pdf', null, true)]
            )
            ->assertStatus(422);

        $this->assertSame($maxFileVirtualId, (int) \App\FileVirtual::max('id'));
    }

    /**
     * @return void
     */
    public function test_upload_user_failure_acl_3()
    {
        $this->fakeStorages();
        $user = $this->authUser('cabinet');
        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_ru_doc_scan', 'status' => 'moderation']);
        $maxFileVirtualId = (int) \App\FileVirtual::max('id');

        $this
            ->post(
                '/api/field/tax_ru_doc_scan/upload/user/' . $user->id,
                ['file' => new UploadedFile(storage_path('app/fake/pdf/01.pdf'), basename('01.pdf'), 'application/pdf', null, true)]
            )
            ->assertStatus(403);

        $this->assertSame($maxFileVirtualId, (int) \App\FileVirtual::max('id'));
    }

    /**
     * @return void
     */
    public function test_upload_user_failure_acl_4()
    {
        $this->fakeStorages();
        $user = $this->authUser('cabinet');
        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form', 'status' => 'draft']);
        $maxFileVirtualId = (int) \App\FileVirtual::max('id');

        $this
            ->post(
                '/api/field/tax_form/upload/user/' . $user->id,
                ['file' => new UploadedFile(storage_path('app/fake/pdf/01.pdf'), basename('01.pdf'), 'application/pdf', null, true)]
            )
            ->assertStatus(422);

        $this->assertSame($maxFileVirtualId, (int) \App\FileVirtual::max('id'));
    }

    /**
     * @return void
     */
    public function test_delete_user_success()
    {
        $user = $this->authUser('cabinet');
        $field1 = \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form', 'status' => 'draft']);
        $field2 = \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_ru_fns_code', 'status' => 'draft']);
        $fields = \App::make(\App\Services\FieldService::class)->fieldsRaw($user);


        // Iteration 1
        $this->post('/api/field/tax_form/delete/user/' . $user->id)
            ->assertStatus(200)
            ->assertJson(['field' => ['id' => $field1->id]]);

        $this->assertNull(\App\Field::find($field1->id));
        $this->assertNotNull(\App\Field::find($field2->id));


        // Iteration 2
        $this->post('/api/field/tax_ru_fns_code/delete/user/' . $user->id)
            ->assertStatus(200)
            ->assertJson(['field' => ['id' => $field2->id]]);

        $this->assertNull(\App\Field::find($field1->id));
        $this->assertNull(\App\Field::find($field2->id));
        $this->assertNotSame($fields, \App::make(\App\Services\FieldService::class)->fieldsRaw($user));
    }

    /**
     * @return void
     */
    public function test_delete_user_failure_not_auth()
    {
        $this
            ->post('/api/field/tax_form/delete/user/1')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_delete_user_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form', 'status' => 'draft']);

        $this->post('/api/field/tax_form/delete/user/' . $user->id)->assertStatus(200);
        $this->post('/api/field/tax_form/delete/user/' . $user->id)->assertStatus(404);
        $this->post('/api/field/tax_form/delete/user/' . ($user->id + 100))->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_delete_user_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form', 'status' => 'draft']);
        $maxFieldId = (int) \App\Field::max('id');

        $this->post('/api/field/tax_form/delete/user/' . $user->id)->assertStatus(403);

        $this->assertSame($maxFieldId, (int) \App\Field::max('id'));
    }

    /**
     * @return void
     */
    public function test_delete_user_failure_acl_2()
    {
        $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $user2 = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        \App\Field::factory()->create(['user_id' => $user2->id, 'name' => 'tax_form', 'status' => 'draft']);
        $maxFieldId = (int) \App\Field::max('id');

        $this->post('/api/field/tax_form/delete/user/' . $user2->id)->assertStatus(404);

        $this->assertSame($maxFieldId, (int) \App\Field::max('id'));
    }

    /**
     * @return void
     */
    public function test_delete_user_failure_acl_3()
    {
        $user = $this->authUser('cabinet');
        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'tax_form', 'status' => 'verified']);
        $maxFieldId = (int) \App\Field::max('id');

        $this->post('/api/field/tax_form/delete/user/' . $user->id)->assertStatus(403);

        $this->assertSame($maxFieldId, (int) \App\Field::max('id'));
    }

    /**
     * @return void
     */
    public function test_update_user_success()
    {
        $user = $this->authUser('cabinet.field.update');
        $field = \App\Field::factory()->create(['name' => 'tax_form', 'user_id' => $user->id, 'status' => 'draft']);


        // Iteration 1
        $this
            ->post(
                '/api/field/tax_form/update/user/' . $user->id,
                ['field' => ['status' => 'moderation']]
            )
            ->assertStatus(200)
            ->assertJson(['field' => ['id' => $field->id]]);

        $this->assertSame('moderation', $field->refresh()->status);


        // Iteration 2
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(false);
        $this
            ->post(
                '/api/field/tax_form/update/user/' . $user->id,
                ['field' => ['status' => 'draft', 'data' => 'bar']]
            )
            ->assertStatus(200)
            ->assertJson(['field' => ['id' => $field->id]]);

        $this->assertSame('draft', $field->refresh()->status);
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);
    }

    /**
     * @return void
     */
    public function test_update_user_failure_not_auth()
    {
        $this
            ->post('/api/field/tax_form/update/user/1', [])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_update_user_failure_not_found()
    {
        $user = $this->authUser('cabinet.field.update');

        $this
            ->post('/api/field/tax_form/update/user/' . $user->id, ['field' => ['status' => 'moderation']])
            ->assertStatus(404);

        $this
            ->post('/api/field/tax_form/update/user/' . ($user->id + 100), ['field' => ['status' => 'moderation']])
            ->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_update_user_failure_validation()
    {
        $user = $this->authUser('cabinet.field.update');
        $field = \App\Field::factory()->create(['name' => 'tax_form', 'user_id' => $user->id, 'status' => 'draft']);

        // Iteration 1
        $this
            ->post('/api/field/tax_form/update/user/' . $user->id, ['field' => ['status' => 'foo']])
            ->assertStatus(422)
            ->assertJson(['errors' => ['field.tax_form.status' => true]]);

        // Iteration 2
        $this
            ->post('/api/field/tax_form/update/user/' . $user->id, ['field' => ['status' => ['moderation']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['field.tax_form.status' => true]]);


        $this->assertSame('draft', $field->refresh()->status);
    }

    /**
     * @return void
     */
    public function test_update_user_failure_acl()
    {
        $user = $this->authUser('cabinet.field.update');
        $field = \App\Field::factory()->create(['name' => 'tax_form', 'user_id' => $user->id, 'status' => 'draft']);

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->post('/api/field/tax_form/update/user/' . $user->id, ['field' => ['status' => 'moderation']])->assertStatus(403);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.field.read']]);
        $this->post('/api/field/tax_form/update/user/' . $user->id, ['field' => ['status' => 'moderation']])->assertStatus(403);

        $this->assertSame('draft', $field->refresh()->status);
    }
}
