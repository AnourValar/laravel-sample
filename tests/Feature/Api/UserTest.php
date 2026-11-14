<?php

namespace Tests\Feature\Api;

use Database\Factories\UserFactory;
use Tests\TestCase;

class UserTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_index_success()
    {
        \App\User::query()->forceDelete();
        $user1 = $this->authUser('cabinet.user.read', ['created_at' => '2024-07-24 10:00']);

        // Iteration 1
        $this->get('/api/user')->assertStatus(200)->assertJsonPath('data', [\App\User::light()->find($user1->id)->toArray()]);

        // Iteration 2
        $user2 = \App\User::factory()->create(['created_at' => '2024-07-24 10:00']);
        $user3 = tap(\App\User::factory()->create(['created_at' => '2024-07-25 10:00']))->delete();

        $this->post('/api/user')->assertStatus(200)->assertExactJson([
            'current_page' => 1,
            'current_page_url' => url('api/user?page=1'),
            'data' => [
                \App\User::withTrashed()->light()->find($user3->id)->toArray(),
                \App\User::light()->find($user2->id)->toArray(),
                \App\User::light()->find($user1->id)->toArray(),
            ],
            'first_page_url' => url('api/user?page=1'),
            'from' => 1,
            'next_page_url' => null,
            'path' => url('api/user'),
            'per_page' => 20,
            'prev_page_url' => null,
            'to' => 3,
        ]);

        // Iteration 3
        $this
            ->post(
                '/api/user',
                ['filter' => ['created_at' => ['>=' => '2024-07-25 00:00']]]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [\App\User::withTrashed()->light()->find($user3->id)->toArray()]);

        // Iteration 4
        $this
            ->post('/api/user', ['per_page' => 1, 'page' => 2, 'sort' => ['id' => 'ASC']])
            ->assertStatus(200)
            ->assertJsonPath('data', [\App\User::light()->find($user2->id)->toArray()]);
    }

    /**
     * @return void
     */
    public function test_index_failure_not_auth()
    {
        $this
            ->get('/api/user')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_index_failure_validation()
    {
        $this->authUser('cabinet');

        $this
            ->post('/api/user', ['filter' => ['foo' => ['=' => 'bar']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['filter.foo.=' => true]]);
    }

    /**
     * @return void
     */
    public function test_index_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->get('/api/user')->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_index_export_success()
    {
        \Date::setTestNow('2025-10-08 10:00:00');
        $this->authUser('cabinet.user.read');
        \App\User::factory()->create();
        \App\User::factory()->create()->delete();

        $this
            ->post('/api/user/index/export/xlsx', ['filter' => ['role' => ['in' => ['admin', 'client', 'staff']]]], ['Accept-Language' => 'ru'])
            ->assertStatus(200)
            ->assertDownload(\Str::ascii('Список пользователей.xlsx'))
            ->streamedContent();

        $this
            ->post('/api/user/index/export/csv', [], ['Accept-Language' => 'ru'])
            ->assertStatus(200)
            ->assertDownload(\Str::ascii('Список пользователей.csv'))
            ->streamedContent();
    }

    /**
     * @return void
     */
    public function test_index_export_failure_not_auth()
    {
        $this
            ->get('/api/user/index/export/xlsx')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_index_export_failure_validation()
    {
        $this->authUser('cabinet');

        $this
            ->post('/api/user/index/export/xlsx', ['filter' => ['foo' => ['=' => 'bar']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['filter.foo.=' => true]]);

        $this
            ->post('/api/user/index/export/pdf')
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => true]]);

        $this
            ->post('/api/user/index/export/foo')
            ->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_index_export_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->get('/api/user/index/export/xlsx')->assertStatus(403);
    }

    /**
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('yesNo')]
    public function test_edit_success(bool $yesNo)
    {
        $this->authUser('cabinet.user.read');
        $user2 = \App\User::factory()->create();
        if ($yesNo) {
            $user2->delete();
        }

        $this->get('/api/user/' . $user2->id)->assertStatus(200)->assertExactJson([
            'user' => \App\User::withTrashed()->heavy()->find($user2->id)->toArray(),
        ]);
    }
    /**
     * @return void
     */
    public function test_edit_failure_not_auth()
    {
        $this
            ->get('/api/user/0')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_edit_failure_not_found()
    {
        $user1 = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create();
        config(["models.user.role.{$user1->role}.abilities" => ['cabinet']]);

        $this->get('/api/user/' . $user2->id)->assertStatus(404);
        $this->get('/api/user/' . ($user2->id + 100))->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_edit_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->get('/api/user/0')->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_create_success_1()
    {
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(false);
        $this->authUser('cabinet.user.create');
        $ref = \App\User::factory()->create();
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $codeEmail);

        $userId = $this
            ->post(
                '/api/user/create',
                [
                    'cryptogram_email' => $cryptogramEmail,
                    'code_email' => $codeEmail,
                    'user' => [
                        'pid' => $ref->pid,
                        'role' => $ref->role,
                        'role_data' => $ref->role_data,
                        'email' => 'foo@example.org',
                        'totp_secret' => ['foo'], // no
                        'additional_abilities' => ['foo'], // no
                        'password' => 'password123456',
                        'first_name' => 'Иван',
                        'last_name' => 'Иванов',
                        'middle_name' => 'Иванович',
                        'gender' => 'male',
                        'birthday' => '2020-06-01',
                        'telegram_username' => ['foo'], // no
                        'tag_ids' => ['foo'], // no
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => true]])
            ->json('user.id');

        $this->assertGreaterThan($ref->id, $userId);
        $this->assertTrue(\Hash::check('password123456', \App\User::find($userId)->password));
        $this->assertSame(
            $ref = [
                'pid' => $ref->pid,
                'role' => $ref->role,
                'role_data' => $ref->role_data,
                'email' => 'foo@example.org',
                'phone' => null,
                'totp_secret' => null,
                'additional_abilities' => null,
                'first_name' => 'Иван',
                'last_name' => 'Иванов',
                'middle_name' => 'Иванович',
                'gender' => 'male',
                'birthday' => '2020-06-01T00:00:00.000000Z',
                'telegram_username' => null,
                'tag_ids' => null,
            ],
            json_decode(json_encode(\App\User::find($userId)->only(array_keys($ref))), true),
        );
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);
    }

    /**
     * @return void
     */
    public function test_create_success_2()
    {
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(false);
        $this->authUser('cabinet.user.create');
        $ref = \App\User::factory()->create();
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $codeEmail);
        $cryptogramPhone = $this->getCryptogramPhone('79000000001', $codePhone);

        $userId = $this
            ->post(
                '/api/user/create',
                [
                    'cryptogram_email' => $cryptogramEmail,
                    'code_email' => $codeEmail,
                    'cryptogram_phone' => $cryptogramPhone,
                    'code_phone' => $codePhone,
                    'user' => [
                        'pid' => $ref->pid,
                        'role' => $ref->role,
                        'role_data' => $ref->role_data,
                        'email' => 'foo@example.org',
                        'phone' => '79000000001',
                        'password' => 'password123456',
                        'first_name' => 'Иван',
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => true]])
            ->json('user.id');

        $this->assertGreaterThan($ref->id, $userId);
        $this->assertTrue(\Hash::check('password123456', \App\User::find($userId)->password));
        $this->assertSame(
            $ref = [
                'pid' => $ref->pid,
                'role' => $ref->role,
                'role_data' => $ref->role_data,
                'email' => 'foo@example.org',
                'phone' => '79000000001',
                'totp_secret' => null,
                'additional_abilities' => null,
                'first_name' => 'Иван',
                'last_name' => null,
                'middle_name' => null,
                'gender' => null,
                'birthday' => null,
                'telegram_username' => null,
            ],
            json_decode(json_encode(\App\User::find($userId)->only(array_keys($ref))), true),
        );
    }

    /**
     * @return void
     */
    public function test_create_failure_not_auth()
    {
        $this->post('/api/user/create', ['user' => ['first_name' => 'foo']])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_create_failure_validation()
    {
        $user = $this->authUser('cabinet.user.create');
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $codeEmail);

        $this
            ->post(
                '/api/user/create',
                [
                    'cryptogram_email' => $cryptogramEmail,
                    'code_email' => $codeEmail,
                    'user' => [
                        'role' => 'foo',
                        'first_name' => ['bar'],
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['user.role' => true, 'user.first_name' => true]]);

        $this->assertSame(\App\User::max('id'), $user->id);
    }

    /**
     * @return void
     */
    public function test_create_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $codeEmail);
        config(["models.user.role.{$user->role}.abilities" => []]);

        $ref = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $data = [
            'role' => $ref->role,
            'role_data' => $ref->role_data,
            'email' => 'foo@example.org',
            'password' => 'password123456',
            'first_name' => 'Иван',
        ];

        $this
            ->post(
                '/api/user/create',
                [
                    'cryptogram_email' => $cryptogramEmail,
                    'code_email' => $codeEmail,
                    'user' => $data,
                ]
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);

        $this->assertSame(\App\User::max('id'), $ref->id);
    }

    /**
     * @return void
     */
    public function test_create_failure_acl_2()
    {
        $user = $this->authUser('cabinet');
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $codeEmail);
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);

        $ref = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => false])]);
        $data = [
            'role' => $ref->role,
            'role_data' => $ref->role_data,
            'email' => 'foo@example.org',
            'password' => 'password123456',
            'first_name' => 'Иван',
        ];

        $this
            ->post(
                '/api/user/create',
                [
                    'cryptogram_email' => $cryptogramEmail,
                    'code_email' => $codeEmail,
                    'user' => $data,
                ]
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);

        $this->assertSame(\App\User::max('id'), $ref->id);
    }

    /**
     * @return void
     */
    public function test_create_failure_acl_3()
    {
        $this->authUser('cabinet.user.create');
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $codeEmail);
        $cryptogramPhone = $this->getCryptogramPhone('79000000001', $codePhone);
        $ref = \App\User::factory()->create();
        $data = [
            'role' => $ref->role,
            'role_data' => $ref->role_data,
            'email' => 'foo@example.org',
            'password' => 'password123456',
            'first_name' => 'Иван',
        ];


        // Iteration 1
        $this
            ->post(
                '/api/user/create',
                [
                    'cryptogram_email' => $cryptogramEmail,
                    'code_email' => ($codeEmail . '2'),
                    'user' => $data,
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_email' => true]]);


        // Iteration 2
        $this
            ->post(
                '/api/user/create',
                [
                    'cryptogram_email' => $cryptogramEmail,
                    'code_email' => $codeEmail,
                    'cryptogram_phone' => $cryptogramPhone,
                    'code_phone' => ($codePhone . '2'),
                    'user' => array_merge($data, ['phone' => '79000000001']),
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_phone' => true]]);


        // Iteration 3
        $this
            ->post('/api/user/create', ['user' => $data])
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => true]]);


        $this->assertSame(\App\User::max('id'), $ref->id);
    }

    /**
     * @return void
     */
    public function test_update_success()
    {
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(false);
        $user = $this->authUser('cabinet');
        $ref = \App\User::factory()->create(['role' => $user->role]);

        $this
            ->post(
                '/api/user/update/' . $user->id,
                [
                    'user' => [
                        'pid' => ['foo'], // no
                        'role_data' => $ref->role_data,
                        'email' => ['foo'], // no
                        'phone' => ['foo'], // no
                        'totp_secret' => ['foo'], // no
                        'additional_abilities' => ['foo'], // no
                        'first_name' => 'Иван',
                        'last_name' => 'Иванов',
                        'middle_name' => 'Иванович',
                        'gender' => 'male',
                        'birthday' => '2020-06-01',
                        'telegram_username' => ['foo'], // no
                        'tag_ids' => ['foo'], // no
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertSame(
            $ref = [
                'pid' => $user->pid,
                'role_data' => $ref->role_data,
                'email' => $user->email,
                'phone' => $user->phone,
                'totp_secret' => $user->totp_secret,
                'additional_abilities' => $user->additional_abilities,
                'first_name' => 'Иван',
                'last_name' => 'Иванов',
                'middle_name' => 'Иванович',
                'gender' => 'male',
                'birthday' => '2020-06-01T00:00:00.000000Z',
                'telegram_username' => $user->telegram_username,
                'tag_ids' => $user->tag_ids,
            ],
            json_decode(json_encode($user->refresh()->only(array_keys($ref))), true),
        );
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);
    }

    /**
     * @return void
     */
    public function test_update_failure_not_auth()
    {
        $this->post('/api/user/update/1', ['user' => ['first_name' => 'foo']])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_update_failure_not_found()
    {
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $user2 = \App\User::factory()->create(['role' => $user->role]);

        $this->post('/api/user/update/' . $user2->id, ['user' => ['first_name' => 'foo']])->assertStatus(404);
        $this->post('/api/user/update/' . ($user2->id + 100), ['user' => ['first_name' => 'foo']])->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_update_failure_validation()
    {
        $user = $this->authUser('cabinet');

        $this
            ->post('/api/user/update/' . $user->id, ['user' => ['first_name' => ['foo']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['user.first_name' => true]]);
    }

    /**
     * @return void
     */
    public function test_update_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->post('/api/user/update/' . $user->id, ['user' => ['first_name' => 'foo']])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_update_failure_acl_2()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $user2 = \App\User::factory()->create(['role' => $user->role]);

        $this->post('/api/user/update/' . $user2->id, ['user' => ['first_name' => 'foo']])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_update_email_success()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org', 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id, 'phone' => '79000000001'],
        ]);

        $this
            ->post(
                '/api/user/update/email/' . $user->id,
                [
                    'cryptogram_new_email' => $this->getCryptogramEmail('FOO2@example.org', $code),
                    'code_new_email' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'email' => 'foo2@EXAMPLE.org',
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertSame('foo2@example.org', $user->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_update_email_failure_not_auth()
    {
        $this->post('/api/user/update/email/1', ['user' => ['email' => 'foo2@example.org']])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_update_email_failure_not_found()
    {
        $user1 = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]), 'email' => 'foo1@example.org', 'totp_secret' => null]);
        $user2 = \App\User::factory()->create(['email' => null, 'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);


        // Iteration 1
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $this
            ->post(
                '/api/user/update/email/' . $user2->id,
                [
                    'cryptogram_new_email' => $this->getCryptogramEmail('foo2@example.org', $code),
                    'code_new_email' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'email' => 'foo2@example.org',
                    ],
                ]
            )
            ->assertStatus(404);


        // Iteration 2
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $this
            ->post(
                '/api/user/update/email/' . ($user2->id + 100),
                [
                    'cryptogram_new_email' => $this->getCryptogramEmail('foo2@example.org', $code),
                    'code_new_email' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'email' => 'foo2@example.org',
                    ],
                ]
            )
            ->assertStatus(404);


        $this->assertSame('foo1@example.org', $user1->refresh()->email);
        $this->assertNull($user2->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_update_email_failure_validation()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org', 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/update/email/' . $user->id,
                [
                    'cryptogram_new_email' => $this->getCryptogramEmail('foo1@example.org', $code),
                    'code_new_email' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => ['email' => 'foo2@example.org'],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_new_email' => true]]);

        $this->assertSame('foo1@example.org', $user->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_update_email_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id],
        ]);

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this
            ->post('/api/user/update/email/' . $user->id, ['cryptograms_fa' => $cryptograms, 'user' => ['email' => 'foo2@example.org']])
            ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_update_email_failure_acl_2()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org']);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id + 1],
        ]);

        $this
            ->post(
                '/api/user/update/email/' . $user->id,
                [
                    'cryptogram_new_email' => $this->getCryptogramEmail('foo2@example.org', $code),
                    'code_new_email' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'email' => 'foo2@example.org',
                    ],
                ]
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['User mismatch.']]]);

        $this->assertSame('foo1@example.org', $user->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_update_email_failure_acl_3()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org']);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id, 'phone' => '79000000001'],
        ]);

        $this
            ->post(
                '/api/user/update/email/' . $user->id,
                [
                    'cryptogram_new_email' => $this->getCryptogramEmail('foo2@example.org'),
                    'code_new_email' => 'foo',
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'email' => 'foo2@example.org',
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_new_email' => true]]);

        $this->assertSame('foo1@example.org', $user->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_update_email_failure_acl_4()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org']);

        $cryptograms = $this->getFa([
            'session' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/update/email/' . $user->id,
                [
                    'cryptogram_new_email' => $this->getCryptogramEmail('foo2@example.org', $code),
                    'code_new_email' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'email' => 'foo2@example.org',
                    ],
                ]
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['Request error.']]]);

        $this->assertSame('foo1@example.org', $user->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_update_phone_success()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org', 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'email' => ['id' => $user->id, 'email' => 'foo1@example.org'],
        ]);

        $this
            ->post(
                '/api/user/update/phone/' . $user->id,
                [
                    'cryptogram_new_phone' => $this->getCryptogramPhone('79000000002', $code),
                    'code_new_phone' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'phone' => '79000000002',
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertSame('79000000002', $user->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_update_phone_failure_not_auth()
    {
        $this->post('/api/user/update/phone/1', ['user' => ['phone' => '79000000002']])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_update_phone_failure_not_found()
    {
        $user1 = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]), 'phone' => '79000000001']);
        $user2 = \App\User::factory()->create(['phone' => null, 'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);


        // Iteration 1
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $this
            ->post(
                '/api/user/update/phone/' . $user2->id,
                [
                    'cryptogram_new_phone' => $this->getCryptogramPhone('79000000002', $code),
                    'code_new_phone' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'phone' => '79000000002',
                    ],
                ]
            )
            ->assertStatus(404);


        // Iteration 2
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $this
            ->post(
                '/api/user/update/phone/' . ($user2->id + 100),
                [
                    'cryptogram_new_phone' => $this->getCryptogramPhone('79000000002', $code),
                    'code_new_phone' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'phone' => '79000000002',
                    ],
                ]
            )
            ->assertStatus(404);


        $this->assertSame('79000000001', $user1->refresh()->phone);
        $this->assertNull($user2->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_update_phone_failure_validation()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org', 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'email' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/update/phone/' . $user->id,
                [
                    'cryptogram_new_phone' => $this->getCryptogramPhone('79000000001', $code),
                    'code_new_phone' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => ['phone' => '79000000002'],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_new_phone' => true]]);

        $this->assertSame('79000000001', $user->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_update_phone_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id],
        ]);

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this
            ->post('/api/user/update/phone/' . $user->id, ['cryptograms_fa' => $cryptograms, 'user' => ['phone' => '79000000002']])
            ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_update_phone_failure_acl_2()
    {
        $user = $this->authUser('cabinet', ['phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'email' => ['id' => $user->id + 1],
        ]);

        $this
            ->post(
                '/api/user/update/phone/' . $user->id,
                [
                    'cryptogram_new_phone' => $this->getCryptogramPhone('79000000002', $code),
                    'code_new_phone' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'phone' => '79000000002',
                    ],
                ]
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['User mismatch.']]]);

        $this->assertSame('79000000001', $user->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_update_phone_failure_acl_4()
    {
        $user = $this->authUser('cabinet', ['phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'email' => ['id' => $user->id, 'phone' => 'foo1@example.org'],
        ]);

        $this
            ->post(
                '/api/user/update/phone/' . $user->id,
                [
                    'cryptogram_new_phone' => $this->getCryptogramPhone('79000000002'),
                    'code_new_phone' => 'foo',
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'phone' => '79000000002',
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_new_phone' => true]]);

        $this->assertSame('79000000001', $user->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_update_phone_failure_acl_5()
    {
        $user = $this->authUser('cabinet', ['phone' => '79000000001']);

        $cryptograms = $this->getFa([
            'session' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/update/phone/' . $user->id,
                [
                    'cryptogram_new_phone' => $this->getCryptogramPhone('79000000002', $code),
                    'code_new_phone' => $code,
                    'cryptograms_fa' => $cryptograms,

                    'user' => [
                        'phone' => '79000000002',
                    ],
                ]
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['Request error.']]]);

        $this->assertSame('79000000001', $user->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_update_telegram_success()
    {
        $user = $this->authUser('cabinet', ['telegram_username' => null, 'telegram_chatid' => null]);


        // Iteration 1
        $this
            ->post('/api/user/update/telegram/' . $user->id, ['user' => ['telegram_username' => 'ivanov_ivan']])
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertSame('ivanov_ivan', $user->refresh()->telegram_username);
        $this->assertSame('@ivanov_ivan', $user->telegram_chatid);


        // Iteration 2
        $this
            ->post('/api/user/update/telegram/' . $user->id, ['user' => ['telegram_username' => null]])
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertNull($user->refresh()->telegram_username);
    }

    /**
     * @return void
     */
    public function test_update_telegram_failure_not_auth()
    {
        $this->post('/api/user/update/telegram/1', ['user' => ['telegram_username' => 'foo']])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_update_telegram_failure_not_found()
    {
        $user = $this->authUser('cabinet.user.update');
        $user2 = tap(\App\User::factory()->create(['telegram_username' => null]))->delete();

        $this->post('/api/user/update/telegram/' . $user2->id, ['user' => ['telegram_username' => 'foo']])->assertStatus(404);
        $this->post('/api/user/update/telegram/' . ($user2->id + 100), ['user' => ['telegram_username' => 'foo']])->assertStatus(404);

        $this->assertNull($user2->refresh()->telegram_username);
    }

    /**
     * @return void
     */
    public function test_update_telegram_failure_validation()
    {
        $user = $this->authUser('cabinet');

        $this
            ->post('/api/user/update/telegram/' . $user->id, ['user' => ['telegram_username' => ['foo']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['user.telegram_username' => true]]);
    }

    /**
     * @return void
     */
    public function test_update_telegram_failure_acl_1()
    {
        $user = $this->authUser('cabinet', ['telegram_username' => null]);
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->post('/api/user/update/telegram/' . $user->id, ['user' => ['telegram_username' => 'foo']])->assertStatus(403);

        $this->assertNull($user->refresh()->telegram_username);
    }

    /**
     * @return void
     */
    public function test_update_telegram_failure_acl_2()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $user2 = \App\User::factory()->create(['role' => $user->role, 'telegram_username' => null]);

        $this->post('/api/user/update/telegram/' . $user2->id, ['user' => ['telegram_username' => 'foo']])->assertStatus(403);

        $this->assertNull($user2->refresh()->telegram_username);
    }

    /**
     * @return void
     */
    public function test_revoke_email_success()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo1@example.org', 'phone' => '79000000001']);
        config(["models.user.role.{$user->role}.rules.email" => []]);
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id],
        ]);

        $this
            ->post('/api/user/revoke/email/' . $user->id, ['cryptograms_fa' => $cryptograms])
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertNull($user->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_revoke_email_failure_not_auth()
    {
        $this->post('/api/user/revoke/email/1', [])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_revoke_email_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['email' => 'foo@example.org']);
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id],
        ]);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $this->post('/api/user/revoke/email/' . ($user2->id), ['cryptograms_fa' => $cryptograms])->assertStatus(404);

        $this->assertSame('foo@example.org', $user2->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_revoke_email_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id],
        ]);

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->post('/api/user/revoke/email/' . $user->id, ['cryptograms_fa' => $cryptograms])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_revoke_email_failure_acl_2()
    {
        $user1 = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $user2 = \App\User::factory()->create(['email' => 'foo@example.org', 'phone' => '79000000001']);
        config(["models.user.role.{$user2->role}.rules.email" => []]);

        config(["models.user.role.{$user1->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $this->post('/api/user/revoke/email/' . $user2->id, ['cryptograms_fa' => $cryptograms])->assertStatus(403);

        $this->assertSame('foo@example.org', $user2->refresh()->email);
    }

    /**
     * @return void
     */
    public function test_revoke_email_failure_acl_3()
    {
        $user = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id + 1],
        ]);

        $this->post('/api/user/revoke/email/' . $user->id, ['cryptograms_fa' => $cryptograms])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_revoke_phone_success()
    {
        $user = $this->authUser('cabinet', ['email' => 'foo@example.org', 'phone' => '79000000001']);
        config(["models.user.role.{$user->role}.rules.phone" => []]);
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id],
        ]);

        $this
            ->post('/api/user/revoke/phone/' . $user->id, ['cryptograms_fa' => $cryptograms])
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertNull($user->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_revoke_phone_failure_not_auth()
    {
        $this->post('/api/user/revoke/phone/1', [])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_revoke_phone_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id],
        ]);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $this->post('/api/user/revoke/phone/' . ($user2->id), ['cryptograms_fa' => $cryptograms])->assertStatus(404);

        $this->assertSame('79000000001', $user2->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_revoke_phone_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id],
        ]);

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->post('/api/user/revoke/phone/' . $user->id, ['cryptograms_fa' => $cryptograms])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_revoke_phone_failure_acl_2()
    {
        $user1 = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $user2 = \App\User::factory()->create(['email' => 'foo@example.org', 'phone' => '79000000001']);
        config(["models.user.role.{$user2->role}.rules.phone" => []]);

        config(["models.user.role.{$user1->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $this->post('/api/user/revoke/phone/' . $user2->id, ['cryptograms_fa' => $cryptograms])->assertStatus(403);

        $this->assertSame('79000000001', $user2->refresh()->phone);
    }

    /**
     * @return void
     */
    public function test_revoke_phone_failure_acl_3()
    {
        $user = $this->authUser('cabinet');
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id + 1],
        ]);

        $this->post('/api/user/revoke/phone/' . $user->id, ['cryptograms_fa' => $cryptograms])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_delete_success()
    {
        \Date::setTestNow('2025-12-18 10:00:00');
        $user1 = $this->authUser('cabinet.user.delete', ['totp_secret' => UserFactory::TOTP_SECRET_REF]);
        $user2 = \App\User::factory()->create();
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $this
            ->post('/api/user/delete/' . $user2->id, ['cryptograms_fa' => $cryptograms])
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user2->id]]);

        $this->assertNull($user1->refresh()->deleted_at);
        $this->assertSame('2025-12-18 10:00:00', $user2->refresh()->deleted_at->format('Y-m-d H:i:s'));
    }

    /**
     * @return void
     */
    public function test_delete_failure_not_auth()
    {
        $this->post('/api/user/delete/0')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_delete_failure_not_found()
    {
        $user = $this->authUser('cabinet.user.read');
        $user2 = tap(\App\User::factory()->create())->delete();

        $cryptograms = $this->getFa([
            'foo' => ['id' => $user->id],
        ]);
        $this->post('/api/user/delete/' . $user2->id, ['cryptograms_fa' => $cryptograms])->assertStatus(404);

        \Cache::clear();
        $this->post('/api/user/delete/' . ($user2->id + 100), ['cryptograms_fa' => $cryptograms])->assertStatus(404);

        $this->assertNull(\App\User::find($user2->id));
    }

    /**
     * @return void
     */
    public function test_delete_failure_validation()
    {
        \Date::setTestNow('2025-12-18 10:00:00');
        $user1 = $this->authUser('cabinet.user.delete', ['totp_secret' => null]);
        $user2 = \App\User::factory()->create();
        $user3 = \App\User::factory()->create(['pid' => $user2->id]);
        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);

        $this
            ->post('/api/user/delete/' . $user2->id, ['cryptograms_fa' => $cryptograms])
            ->assertStatus(422)
            ->assertJson(['errors' => ['id' => true]]);

        $this->assertNull($user2->refresh()->deleted_at);
        $this->assertNull($user3->refresh()->deleted_at);
    }

    /**
     * @return void
     */
    public function test_delete_failure_acl_1()
    {
        $user1 = $this->authUser('cabinet.user.delete');
        $user2 = \App\User::factory()->create();

        config(["models.user.role.{$user1->role}.abilities" => ['foo']]);
        $this->post('/api/user/delete/' . $user1->id)->assertStatus(403);

        config(["models.user.role.{$user1->role}.abilities" => ['cabinet']]);
        $this->post('/api/user/delete/' . $user1->id)->assertStatus(403);

        $cryptograms = $this->getFa([
            'foo' => ['id' => $user1->id],
        ]);
        $this->post('/api/user/delete/' . $user2->id, ['cryptograms_fa' => $cryptograms])->assertStatus(404);

        \Cache::clear();
        config(["models.user.role.{$user1->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $this->post('/api/user/delete/' . $user2->id, ['cryptograms_fa' => $cryptograms])->assertStatus(403);

        $this->assertNull($user1->refresh()->deleted_at);
        $this->assertNull($user2->refresh()->deleted_at);
    }

    /**
     * @return void
     */
    public function test_delete_failure_acl_2()
    {
        $user = $this->authUser('cabinet.user.delete');

        $this->post('/api/user/delete/' . $user->id)->assertStatus(403)->assertJson(['errors' => ['error' => ['Confirmation of factors required: 1-5']]]);
        $this->assertNull($user->refresh()->deleted_at);
    }

    /**
     * @return void
     */
    public function test_restore_success()
    {
        $this->authUser('cabinet.user.delete');
        $user2 = tap(\App\User::factory()->create())->delete();

        $this->post('/api/user/restore/' . $user2->id)
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user2->id]]);

        $this->assertNotNull(\App\User::find($user2->id));
    }

    /**
     * @return void
     */
    public function test_restore_failure_not_auth()
    {
        $this
            ->post('/api/user/restore/1')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_restore_failure_not_found()
    {
        $user = $this->authUser('cabinet');

        $this->post('/api/user/restore/' . $user->id)->assertStatus(404);

        $this->assertNotNull(\App\User::find($user->id));
    }

    /**
     * @return void
     */
    public function test_restore_failure_validation()
    {
        \Date::setTestNow('2025-12-18 10:00:00');
        $this->authUser('cabinet.user.delete');
        $user2 = tap(\App\User::factory()->create())->delete();
        $user3 = tap(\App\User::factory()->create(['pid' => $user2->id]))->delete();

        $this
            ->post('/api/user/restore/' . $user3->id)
            ->assertStatus(422)
            ->assertJson(['errors' => ['id' => true]]);

        $this->assertNotNull($user2->refresh()->deleted_at);
        $this->assertNotNull($user3->refresh()->deleted_at);
    }

    /**
     * @return void
     */
    public function test_restore_failure_acl()
    {
        $user1 = $this->authUser('cabinet');
        $user2 = tap(\App\User::factory()->create())->delete();

        config(["models.user.role.{$user1->role}.abilities" => ['foo']]);
        $this->post('/api/user/restore/' . $user2->id)->assertStatus(403);

        config(["models.user.role.{$user1->role}.abilities" => ['cabinet']]);
        $this->post('/api/user/restore/' . $user2->id)->assertStatus(404);

        config(["models.user.role.{$user1->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $this->post('/api/user/restore/' . $user2->id)->assertStatus(403);

        $this->assertNull(\App\User::find($user2->id));
    }

    /**
     * @return void
     */
    public function test_totp_generate_success()
    {
        $this->authUser('cabinet', ['phone' => '79000000001']);
        $confirmService = \App::make(\AnourValar\EloquentNotification\ConfirmService::class);

        $response = $this
            ->post('/api/user/totp/generate')
            ->assertStatus(200)
            ->assertJson(['secret' => true, 'secret_url' => true, 'cryptogram_totp' => true])
            ->json();

        $this->assertSame($response['secret_url'], $confirmService->urlTotp('79000000001', $response['secret']));
        $this->assertSame(['type' => 'confirm.totp', 'secret' => $response['secret']], json_decode(decrypt($response['cryptogram_totp'], false), true));
    }

    /**
     * @return void
     */
    public function test_totp_generate_failure_not_auth()
    {
        $this->post('/api/user/totp/generate')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_totp_generate_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->post('/api/user/totp/generate')->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_totp_update_success()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = $this->authUser('cabinet', ['totp_secret' => null]);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id],
        ]);
        $totp = \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->cryptogramTotp(UserFactory::TOTP_SECRET_REF);

        $this
            ->post(
                '/api/user/totp/update',
                ['cryptograms_fa' => $cryptograms, 'cryptogram_new_totp' => $totp, 'code_new_totp' => '080199']
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertSame($totp, $user->refresh()->totp_secret);
    }

    /**
     * @return void
     */
    public function test_totp_update_failure_not_auth()
    {
        $this->post('/api/user/totp/update', [])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_totp_update_failure_acl_1()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = $this->authUser('cabinet', ['totp_secret' => null]);
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->post('/api/user/totp/update')->assertStatus(403);

        $this->assertNull($user->refresh()->totp_secret);
    }

    /**
     * @return void
     */
    public function test_totp_update_failure_acl_2()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = $this->authUser('cabinet', ['totp_secret' => null]);
        $totp = \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->cryptogramTotp(UserFactory::TOTP_SECRET_REF);


        // Iteration 1
        $cryptograms = $this->getFa([
            'session' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/totp/update',
                ['cryptograms_fa' => $cryptograms, 'cryptogram_new_totp' => $totp, 'code_new_totp' => '080199']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);


        // Iteration 2
        $this
            ->post(
                '/api/user/totp/update',
                ['cryptogram_new_totp' => $totp, 'code_new_totp' => '080199']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);


        $this->assertNull($user->refresh()->totp_secret);
    }

    /**
     * @return void
     */
    public function test_totp_update_failure_acl_3()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = $this->authUser('cabinet', ['totp_secret' => null]);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/totp/update',
                ['cryptograms_fa' => $cryptograms, 'cryptogram_new_totp' => UserFactory::TOTP_SECRET_REF, 'code_new_totp' => '080198']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_new_totp' => true]]);

        $this
            ->post(
                '/api/user/totp/update',
                ['cryptograms_fa' => $cryptograms, 'cryptogram_new_totp' => encrypt('foo'), 'code_new_totp' => '080199']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_new_totp' => true]]);

        $this->assertNull($user->refresh()->totp_secret);
    }

    /**
     * @return void
     */
    public function test_totp_revoke_success()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = $this->authUser('cabinet', ['totp_secret' => UserFactory::TOTP_SECRET_REF]);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/totp/revoke',
                ['cryptograms_fa' => $cryptograms]
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertNull($user->refresh()->totp_secret);
    }

    /**
     * @return void
     */
    public function test_totp_revoke_failure_not_auth()
    {
        $this->post('/api/user/totp/revoke')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_totp_revoke_failure_acl_1()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = $this->authUser('cabinet', ['totp_secret' => UserFactory::TOTP_SECRET_REF]);
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->post('/api/user/totp/revoke')->assertStatus(403);

        $this->assertNotNull($user->refresh()->totp_secret);
    }

    /**
     * @return void
     */
    public function test_totp_revoke_failure_acl_2()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = $this->authUser('cabinet', ['totp_secret' => null]);


        // Iteration 1
        $cryptograms = $this->getFa([
            'session' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/user/totp/revoke',
                ['cryptograms_fa' => $cryptograms]
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);


        // Iteration 2
        $this
            ->post('/api/user/totp/revoke')
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);


        $this->assertNull($user->refresh()->totp_secret);
    }
}
