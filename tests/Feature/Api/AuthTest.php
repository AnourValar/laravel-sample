<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class AuthTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_confirm_pow_success()
    {
        $response = $this
            ->post('/api/auth/confirm/pow')
            ->assertStatus(200)
            ->assertJson([
                'salt' => true,
                'cryptogram_pow' => true,
                'puzzle_pow' => true,
            ])
            ->json();

        $this->assertSame('confirm.pow', decrypt($response['cryptogram_pow'])['type']);
    }

    /**
     * @return void
     */
    public function test_confirm_email_success()
    {
        //$this->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class);

        // As guest
        $response = $this
            ->post('/api/auth/confirm/email', ['email' => 'foo@example.org'], ['Accept-Language' => 'ru'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_email' => true]);

        $this->assertSame('confirm.email', decrypt($response['cryptogram_email'])['type']);


        // As logged-in
        \App\User::factory()->create(['email' => 'bar@example.org']);
        $this->authUser('cabinet');

        $response = $this
            ->post('/api/auth/confirm/email', ['email' => encrypt('bar@example.org')], ['Accept-Language' => 'en'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_email' => true]);

        $this->assertSame('confirm.email', decrypt($response['cryptogram_email'])['type']);
    }

    /**
     * @return void
     */
    public function test_confirm_email_emailShouldExists()
    {
        \Date::setTestNow('2024-06-03 12:40:00');
        \App\User::factory()->create(['email' => 'bar@example.org']);
        //$this->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class);

        // true -> success
        $this
            ->post('/api/auth/confirm/email', ['email' => 'bar@example.org', 'email_should_exists' => true])
            ->assertStatus(200)
            ->assertJson(['cryptogram_email' => true]);

        // true -> failure
        $this
            ->post('/api/auth/confirm/email', ['email' => 'foo@example.org', 'email_should_exists' => true])
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => true]]);

        // false -> success
        $this
            ->post('/api/auth/confirm/email', ['email' => 'foo@example.org', 'email_should_exists' => false])
            ->assertStatus(200)
            ->assertJson(['cryptogram_email' => true]);

        // false -> failure
        $this
            ->post('/api/auth/confirm/email', ['email' => 'bar@example.org', 'email_should_exists' => false])
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => true]]);
    }

    /**
     * @return void
     */
    public function test_confirm_email_failure_validation()
    {
        $this
            //->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class)
            ->post('/api/auth/confirm/email', ['email' => 'bar', 'email_should_exists' => ['foo']])
            ->assertStatus(422)
            ->assertJson(['errors' => ['email' => true, 'email_should_exists' => true]]);
    }

    /**
     * @return void
     */
    public function test_confirm_email_failure_acl()
    {
        //$this->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class);

        $this->post('/api/auth/confirm/email', ['email' => 'foo@example.org'])->assertStatus(200);
        $this->post('/api/auth/confirm/email', ['email' => 'foo@example.org'])->assertStatus(200);
        $this->post('/api/auth/confirm/email', ['email' => 'FOO@EXAMPLE.ORG'])->assertStatus(429);
    }

    /**
     * @return void
     */
    public function test_confirm_phone_success()
    {
        $this->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class);
        $codes = [];


        // As guest
        $response = $this
            ->post('/api/auth/confirm/phone', ['phone' => '79000000001'], ['Accept-Language' => 'ru'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_phone' => true]);

        $this->assertSame('confirm.phone', decrypt($response['cryptogram_phone'])['type']);
        $codes[] = decrypt($response['cryptogram_phone'])['code'];


        // As logged-in
        \App\User::factory()->create(['phone' => '79000000002']);
        $this->authUser('cabinet');

        $response = $this
            ->post('/api/auth/confirm/phone', ['phone' => encrypt('79000000002')], ['Accept-Language' => 'en'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_phone' => true]);

        $this->assertSame('confirm.phone', decrypt($response['cryptogram_phone'])['type']);
        $codes[] = decrypt($response['cryptogram_phone'])['code'];


        // Demo check
        $demoCode = \App::make(\App\Services\SettingService::class)->get('user_demo')['code'];
        $this->assertNotEquals([$demoCode, $demoCode], $codes);
    }

    /**
     * @return void
     */
    public function test_confirm_phone_phoneShouldExists()
    {
        \Date::setTestNow('2024-06-03 12:40:00');
        \App\User::factory()->create(['phone' => '79000000002']);
        $this->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class);

        // true -> success
        $this
            ->post('/api/auth/confirm/phone', ['phone' => '79000000002', 'phone_should_exists' => true])
            ->assertStatus(200)
            ->assertJson(['cryptogram_phone' => true]);

        // true -> failure
        $this
            ->post('/api/auth/confirm/phone', ['phone' => '79000000001', 'phone_should_exists' => true])
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => true]]);

        // false -> success
        $this
            ->post('/api/auth/confirm/phone', ['phone' => '79000000001', 'phone_should_exists' => false])
            ->assertStatus(200)
            ->assertJson(['cryptogram_phone' => true]);

        // false -> failure
        $this
            ->post('/api/auth/confirm/phone', ['phone' => '79000000002', 'phone_should_exists' => false])
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => true]]);
    }

    /**
     * @return void
     */
    public function test_confirm_phone_failure_validation()
    {
        $this
            ->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class)
            ->post('/api/auth/confirm/phone', ['phone' => 'bar', 'phone_should_exists' => ['foo']])
            ->assertStatus(422)
            ->assertJson(['errors' => ['phone' => true, 'phone_should_exists' => true]]);
    }

    /**
     * @return void
     */
    public function test_confirm_phone_failure_acl()
    {
        $this->withoutMiddleware(\AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class);

        $this->post('/api/auth/confirm/phone', ['phone' => '79000000001'])->assertStatus(200);
        $this->post('/api/auth/confirm/phone', ['phone' => '79000000001'])->assertStatus(200);
        $this->post('/api/auth/confirm/phone', ['phone' => '79000000001'])->assertStatus(429);
    }

    /**
     * @return void
     */
    public function test_fa_phone_success()
    {
        // No user
        $cryptogramPhone = $this->getCryptogramPhone('79000000001', $code);

        $response = $this
            ->post('/api/auth/fa/phone', ['cryptogram_phone' => $cryptogramPhone, 'code_phone' => $code, 'phone' => '79000000001'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true]);

        $this->assertSame('phone', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['phone' => '79000000001'], decrypt($response['cryptogram_fa'])->contacts);
        $this->assertSame(\App::make(\AnourValar\EloquentNotification\ConfirmService::class)->fa(null), $response['factors']);


        // With user
        $user = \App\User::factory()->create([
            'phone' => '79000000001',
            'email' => 'foo@example.org',
            'password' => \Hash::make('password12345'),
            'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]),
        ]);
        $cryptogramPhone = $this->getCryptogramPhone('79000000001', $code);

        $response = $this
            ->post('/api/auth/fa/phone', ['cryptogram_phone' => $cryptogramPhone, 'code_phone' => $code, 'phone' => encrypt('79000000001')])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame('phone', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['id' => $user->id, 'phone' => '79000000001'], decrypt($response['cryptogram_fa'])->contacts);
        $this->compareFactors($user, $response);


        // With deleted user
        \Cache::clear();
        $user->delete();
        $cryptogramPhone = $this->getCryptogramPhone('79000000001', $code);

        $response = $this
            ->post('/api/auth/fa/phone', ['cryptogram_phone' => $cryptogramPhone, 'code_phone' => $code, 'phone' => encrypt('79000000001')])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame('phone', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['phone' => '79000000001'], decrypt($response['cryptogram_fa'])->contacts);
        $this->assertSame(\App::make(\AnourValar\EloquentNotification\ConfirmService::class)->fa(null), $response['factors']);


        // With unsupported user
        $user->restore();
        config(["models.user.role.{$user->role}.abilities" => []]);
        $cryptogramPhone = $this->getCryptogramPhone('79000000001', $code);

        $response = $this
            ->post('/api/auth/fa/phone', ['cryptogram_phone' => $cryptogramPhone, 'code_phone' => $code, 'phone' => encrypt('79000000001')])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame('phone', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['phone' => '79000000001'], decrypt($response['cryptogram_fa'])->contacts);
        $this->assertSame(\App::make(\AnourValar\EloquentNotification\ConfirmService::class)->fa(null), $response['factors']);
    }

    /**
     * @return void
     */
    public function test_fa_phone_failure()
    {
        // No user
        $cryptogramPhone = $this->getCryptogramPhone('79000000001');

        $this
            ->post('/api/auth/fa/phone', ['cryptogram_phone' => $cryptogramPhone, 'code_phone' => '123', 'phone' => '79000000001'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_phone' => true]]);


        // With user
        \App\User::factory()->create([
            'phone' => '79000000001',
            'email' => 'foo@example.org',
            'password' => \Hash::make('password12345'),
            'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]),
        ]);
        $cryptogramPhone = $this->getCryptogramPhone('79000000001', $code);

        $this
            ->post('/api/auth/fa/phone', ['cryptogram_phone' => $cryptogramPhone, 'code_phone' => $code, 'phone' => '79000000002'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_phone' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_email_success()
    {
        // No user
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $code);

        $response = $this
            ->post('/api/auth/fa/email', ['cryptogram_email' => $cryptogramEmail, 'code_email' => $code, 'email' => 'foo@example.org'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame('email', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['email' => 'foo@example.org'], decrypt($response['cryptogram_fa'])->contacts);
        $this->assertSame(\App::make(\AnourValar\EloquentNotification\ConfirmService::class)->fa(null), $response['factors']);


        // With user
        $user = \App\User::factory()->create([
            'phone' => '79000000001',
            'email' => 'foo@example.org',
            'password' => \Hash::make('password12345'),
            'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]),
        ]);
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $code);

        $response = $this
            ->post('/api/auth/fa/email', ['cryptogram_email' => $cryptogramEmail, 'code_email' => $code, 'email' => encrypt('foo@example.org')])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true]);

        $this->assertSame('email', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['id' => $user->id, 'email' => 'foo@example.org'], decrypt($response['cryptogram_fa'])->contacts);
        $this->compareFactors($user, $response);


        // With deleted user
        \Cache::clear();
        $user->delete();
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $code);

        $response = $this
            ->post('/api/auth/fa/email', ['cryptogram_email' => $cryptogramEmail, 'code_email' => $code, 'email' => encrypt('foo@example.org')])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true]);

        $this->assertSame('email', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['email' => 'foo@example.org'], decrypt($response['cryptogram_fa'])->contacts);
        $this->assertSame(\App::make(\AnourValar\EloquentNotification\ConfirmService::class)->fa(null), $response['factors']);


        // With unsupported user
        $user->restore();
        config(["models.user.role.{$user->role}.abilities" => []]);
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $code);

        $response = $this
            ->post('/api/auth/fa/email', ['cryptogram_email' => $cryptogramEmail, 'code_email' => $code, 'email' => encrypt('foo@example.org')])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true]);

        $this->assertSame('email', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['email' => 'foo@example.org'], decrypt($response['cryptogram_fa'])->contacts);
        $this->assertSame(\App::make(\AnourValar\EloquentNotification\ConfirmService::class)->fa(null), $response['factors']);
    }

    /**
     * @return void
     */
    public function test_fa_email_failure()
    {
        // No user
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $code);

        $this
            ->post('/api/auth/fa/email', ['cryptogram_email' => $cryptogramEmail, 'code_email' => '123', 'email' => 'foo@example.org'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_email' => true]]);


        // With user
        \App\User::factory()->create([
            'phone' => '79000000001',
            'email' => 'foo@example.org',
            'password' => \Hash::make('password12345'),
            'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]),
        ]);
        $cryptogramEmail = $this->getCryptogramEmail('foo@example.org', $code);

        $this
            ->post('/api/auth/fa/email', ['cryptogram_email' => $cryptogramEmail, 'code_email' => $code, 'email' => 'foo2@example.org'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_email' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_password_success()
    {
        $user = \App\User::factory()->create([
            'phone' => '79000000001',
            'email' => 'foo@example.org',
            'password' => password_hash('password12345', PASSWORD_BCRYPT, ['cost' => 5]),
            'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]),
        ]);

        // By phone
        $response = $this
            ->post('/api/auth/fa/password', ['login' => '79000000001', 'password' => 'password12345'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $password = $user->fresh()->password;
        $this->assertNotSame($user->password, $password);
        $this->assertTrue(\Hash::check('password12345', $password));
        $this->assertFalse(\Hash::needsRehash($password));

        $this->assertSame('password', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['id' => $user->id], decrypt($response['cryptogram_fa'])->contacts);
        $this->compareFactors($user, $response);


        // By email
        $response = $this
            ->post('/api/auth/fa/password', ['login' => 'foo@example.org', 'password' => 'password12345'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame($password, $user->fresh()->password);

        $this->assertSame('password', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['id' => $user->id], decrypt($response['cryptogram_fa'])->contacts);
        $this->compareFactors($user, $response);
    }

    /**
     * @return void
     */
    public function test_fa_password_failure_not_found()
    {
        $this
            ->withHeader('Accept-language', 'ru')
            ->post('/api/auth/fa/password', ['login' => '79000000001', 'password' => 'password12345'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => ['Некорректный пароль.']]]);
    }

    /**
     * @return void
     */
    public function test_fa_password_failure_validation()
    {
        $this
            ->withHeader('Accept-language', 'ru')
            ->post('/api/auth/fa/password', ['login' => ['79000000001'], 'password' => ['password12345']])
            ->assertStatus(422)
            ->assertJson(['errors' => ['login' => true, 'password' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_password_failure_acl_1()
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        \App\User::factory()->create([
            'phone' => '79000000001',
            'email' => 'foo@example.org',
            'password' => \Hash::make('password12345'),
            'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]),
        ]);

        for ($i = 0; $i < 20; $i++) {
            $this
                ->withHeader('Accept-language', 'ru')
                ->post('/api/auth/fa/password', ['login' => '79000000001', 'password' => 'password123456'])
                ->assertStatus(422)
                ->assertJson(['errors' => ['error' => ['Некорректный пароль.']]]);
        }

        $this
            ->withHeader('Accept-language', 'ru')
            ->post('/api/auth/fa/password', ['login' => '79000000001', 'password' => 'password123456'])
            ->assertStatus(429)
            ->assertJson(['errors' => ['error' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_password_failure_acl_2()
    {
        $user = \App\User::factory()->create([
            'phone' => '79000000001',
            'email' => 'foo@example.org',
            'password' => \Hash::make('password12345'),
        ]);
        config(["models.user.role.{$user->role}.abilities" => []]);

        $this
            ->withHeader('Accept-language', 'ru')
            ->post('/api/auth/fa/password', ['login' => '79000000001', 'password' => 'password12345'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['error' => ['Некорректный пароль.']]]);
    }

    /**
     * @return void
     */
    public function test_fa_session_success()
    {
        $user = $this->authUser('cabinet');

        $response = $this
            ->post('/api/auth/fa/session')
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame('session', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['id' => $user->id], decrypt($response['cryptogram_fa'])->contacts);
        $this->compareFactors($user, $response);
    }

    /**
     * @return void
     */
    public function test_fa_session_failure_not_auth()
    {
        $this
            ->post('/api/auth/fa/session')
            ->assertStatus(401)
            ->assertJson(['message' => true, 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_fa_session_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this
            ->post('/api/auth/fa/session')
            ->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_fa_totp_success()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = \App\User::factory()->totpFixed()->hasAbility('cabinet')->create(['phone' => '79000000001', 'email' => 'foo@example.org']);


        // Iteration 1
        $response = $this
            ->post('/api/auth/fa/totp', ['login' => '79000000001', 'code_totp' => '080199'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame('totp', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['id' => $user->id], decrypt($response['cryptogram_fa'])->contacts);
        $this->compareFactors($user, $response);


        // Iteration 2
        $response = $this
            ->post('/api/auth/fa/totp', ['login' => 'foo@example.org', 'code_totp' => '080199'])
            ->assertStatus(200)
            ->assertJson(['cryptogram_fa' => true, 'factors' => true]);

        $this->assertSame('totp', decrypt($response['cryptogram_fa'])->name);
        $this->assertSame(['id' => $user->id], decrypt($response['cryptogram_fa'])->contacts);
        $this->compareFactors($user, $response);
    }

    /**
     * @return void
     */
    public function test_fa_totp_failure_not_found()
    {
        $this
            ->post('/api/auth/fa/totp', ['login' => '79000000002', 'code_totp' => '123456'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_totp' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_totp_failure_validation()
    {
        $this
            ->post('/api/auth/fa/totp', ['login' => ['foo'], 'code_totp' => ['bar']])
            ->assertStatus(422)
            ->assertJson(['errors' => ['login' => true, 'code_totp' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_totp_failure_acl_1()
    {
        \Date::setTestNow('2025-10-14 10:50:00');

        $this
            ->post('/api/auth/fa/totp', ['login' => '79000000001', 'code_totp' => '080199'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_totp' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_totp_failure_acl_2()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        \App\User::factory()->totpFixed()->hasAbility('cabinet')->create(['phone' => '79000000001']);

        $this
            ->post('/api/auth/fa/totp', ['login' => '79000000001', 'code_totp' => '080198'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_totp' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_totp_failure_acl_3()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        \App\User::factory()->hasAbility('cabinet')->create(['phone' => '79000000001', 'totp_secret' => null]);

        $this
            ->post('/api/auth/fa/totp', ['login' => '79000000001', 'code_totp' => '080199'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_totp' => true]]);
    }

    /**
     * @return void
     */
    public function test_fa_totp_failure_acl_4()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = \App\User::factory()->hasAbility('cabinet')->totpFixed()->create(['phone' => '79000000001']);
        config(["models.user.role.{$user->role}.abilities" => []]);

        $this
            ->post('/api/auth/fa/totp', ['login' => '79000000001', 'code_totp' => '080199'])
            ->assertStatus(422)
            ->assertJson(['errors' => ['code_totp' => true]]);
    }

    /**
     * @return void
     */
    public function test_change_password_success()
    {
        $role = \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]);
        $user = \App\User::factory()->create(['role' => $role, 'email' => 'foo@example.org', 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id, 'phone' => '79000000001'],
            'email' => ['id' => $user->id, 'email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms, 'password' => 'password123456']
            )
            ->assertStatus(200)
            ->assertExactJson(['user' => ['id' => $user->id]]);

        $this->assertTrue(\Hash::check('password123456', $user->refresh()->password));
    }

    /**
     * @return void
     */
    public function test_change_password_failure_deleted()
    {
        $role = \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]);
        $user = tap(\App\User::factory()->create(['role' => $role, 'email' => 'foo@example.org', 'phone' => '79000000001']))->delete();
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id, 'phone' => '79000000001'],
            'email' => ['id' => $user->id, 'email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms, 'password' => 'password123456']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => [trans('controllers/api/auth.createToken_deleted')]]]);

        $this->assertFalse(\Hash::check('password123456', $user->refresh()->password));
    }

    /**
     * @return void
     */
    public function test_change_password_failure_not_exists()
    {
        $cryptograms = $this->getFa([
            'foo' => ['phone' => '79000000001'],
            'bar' => ['phone' => '79000000001'],
        ]);

        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms, 'password' => 'password123456']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => [trans('controllers/api/auth.createToken_not_exists')]]]);
    }

    /**
     * @return void
     */
    public function test_change_password_failure_validation()
    {
        $role = \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]);
        $user = \App\User::factory()->create(['role' => $role, 'email' => 'foo@example.org', 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
           'phone' => ['id' => $user->id, 'phone' => '79000000001'],
           'email' => ['id' => $user->id, 'email' => 'foo@example.org'],
        ]);

        \Cache::flush();
        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['password' => true]]);

        \Cache::flush();
        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms, 'password' => 'foo']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['password' => true]]);

        \Cache::flush();
        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms, 'password' => ['foobarbaz12345']]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['password' => true]]);

        $this->assertFalse(\Hash::check('password123456', $user->refresh()->password));
    }

    /**
     * @return void
     */
    public function test_change_password_failure_acl_1()
    {
        $user = \App\User::factory()->create(['email' => 'foo@example.org', 'phone' => '79000000001', 'additional_abilities' => null]);
        config(["models.user.role.{$user->role}.abilities" => []]);
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id, 'phone' => '79000000001'],
            'email' => ['id' => $user->id, 'email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms, 'password' => 'password123456']
            )
            ->assertStatus(403);

        $this->assertFalse(\Hash::check('password123456', $user->refresh()->password));
    }

    /**
     * @return void
     */
    public function test_change_password_failure_acl_2()
    {
        $role = \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]);
        $user = \App\User::factory()->create(['role' => $role, 'email' => 'foo@example.org', 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'phone' => ['phone' => '79000000001'],
            'email' => ['email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/change/password',
                ['cryptograms_fa' => $cryptograms, 'password' => 'password123456']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['Request error.']]]);

        $this->assertFalse(\Hash::check('password123456', $user->refresh()->password));
    }

    /**
     * @return void
     */
    public function test_register_success()
    {
        \Date::setTestNow('2025-08-19 17:00:00');
        $cryptograms = $this->getFa([
            'email' => ['email' => 'foo@example.org'],
        ]);

        $role = \ConfigHelper::randomKey(
            'models.user.role',
            fn ($value) => ! empty($value['is_customer']) && ! \ConfigHelper::hasItem($value['rules']['pid'] ?? [], 'required')
        );
        $roleData = \App::make(config("models.user.role.{$role}.bind"))->generateFake($role, \App::make(\Faker\Generator::class));

        $token = $this
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.2'])
            ->post(
                '/api/auth/register',
                [
                    'cryptograms_fa' => $cryptograms,
                    'device' => 'foo',
                    'password' => '12345678',
                    'first_name' => 'foo',
                    'last_name' => 'bar',
                    'middle_name' => 'baz',
                    'role' => $role,
                    'role_data' => $roleData,
                    'email' => ['foo'], // no
                    'phone' => ['foo'], // no
                    'totp_secret' => ['foo'], // no
                    'additional_abilities' => ['foo'], // no
                ]
            )
            ->assertStatus(200)
            ->assertJson(['token' => true])
            ->json('token');

        $user = \App\User::latest('id')->first();
        $this->assertSame(
            $ref = [
                'role' => $role,
                'role_data' => $roleData,
                'email' => 'foo@example.org',
                'phone' => null,
                'first_name' => 'foo',
                'last_name' => 'bar',
                'middle_name' => 'baz',
            ],
            $user->only(array_keys($ref))
        );
        $this->assertTrue(\Hash::check('12345678', $user->password));

        $this->get('/api/auth/state', ['Authorization' => "Bearer {$token}"])->assertStatus(200);
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('2026-08-19T17:00:00.000000Z', $user->tokens()->first()->expires_at->toISOString());
        $this->assertSame(
            ['name' => 'foo', 'abilities' => ['*'], 'last_used_at' => '2025-08-19T17:00:00.000000Z'],
            json_decode(json_encode($user->tokens()->first()->only('name', 'abilities', 'last_used_at')), true)
        );

        $this->assertNotNull(\App\UserNotification::where('user_id', '=', $user->id)->first());
        $this->assertSame(
            [
                'user_id' => $user->id,
                'type' => 'metric',
                'event' => 'user_token_obtain',
                'ip_address' => '127.0.0.2',
            ],
            \App\Journal::latest('id')->select(['user_id', 'type', 'event', 'ip_address'])->first()->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_register_failure_exists()
    {
        $user = \App\User::factory()->create(['email' => 'foo@example.org']);
        $maxUserId = (int) \App\User::withTrashed()->max('id');

        $role = \ConfigHelper::randomKey(
            'models.user.role',
            fn ($value) => ! empty($value['is_customer']) && ! \ConfigHelper::hasItem($value['rules']['pid'] ?? [], 'required')
        );
        $roleData = \App::make(config("models.user.role.{$role}.bind"))->generateFake($role, \App::make(\Faker\Generator::class));


        // Iteration 1
        $cryptograms = $this->getFa([
            'email' => ['email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'device' => 'foo', 'role' => $role, 'role_data' => $roleData, 'password' => '12345678']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['email' => true]]);


        // Iteration 2
        $user->delete();
        $cryptograms = $this->getFa([
            'email' => ['email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'device' => 'foo', 'role' => $role, 'role_data' => $roleData, 'password' => '12345678']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['email' => true]]);

        $this->assertSame($maxUserId, (int) \App\User::withTrashed()->max('id'));
    }

    /**
     * @return void
     */
    public function test_register_failure_validation()
    {
        $maxUserId = (int) \App\User::withTrashed()->max('id');

        $role = \ConfigHelper::randomKey(
            'models.user.role',
            fn ($value) => ! empty($value['is_customer']) && ! \ConfigHelper::hasItem($value['rules']['pid'] ?? [], 'required')
        );
        $roleData = \App::make(config("models.user.role.{$role}.bind"))->generateFake($role, \App::make(\Faker\Generator::class));


        // Iteration 1
        $cryptograms = $this->getFa([
            'email' => ['email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'device' => 'foo', 'role' => $role, 'role_data' => $roleData]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['role' => [trans('models/user.role_factors_not_enough', ['qty' => 2])]]]);


        // Iteration 2
        $cryptograms = $this->getFa([
            'email' => ['email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'device' => 'foo', 'role' => $role, 'role_data' => $roleData, 'password' => '123']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['password' => true]]);


        // Iteration 3
        $cryptograms = $this->getFa([
            'email' => ['email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'role' => $role, 'role_data' => $roleData, 'password' => '12345678']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['device' => true]]);


        $this->assertSame($maxUserId, (int) \App\User::withTrashed()->max('id'));
    }

    /**
     * @return void
     */
    public function test_register_failure_acl()
    {
        $maxUserId = (int) \App\User::withTrashed()->max('id');

        $role = \ConfigHelper::randomKey(
            'models.user.role',
            fn ($value) => ! empty($value['is_customer']) && ! \ConfigHelper::hasItem($value['rules']['pid'] ?? [], 'required')
        );
        $roleData = \App::make(config("models.user.role.{$role}.bind"))->generateFake($role, \App::make(\Faker\Generator::class));


        // Iteration 1
        $cryptograms = [
            encrypt(''),
        ];

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'device' => 'foo', 'role' => $role, 'role_data' => $roleData, 'password' => '12345678']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);


        // Iteration 2
        $cryptograms = $this->getFa([
            'phone' => ['id' => 1],
        ]);

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'device' => 'foo', 'role' => $role, 'role_data' => $roleData, 'password' => '12345678']
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['role' => [trans('models/user.role_factors_not_enough', ['qty' => 2])]]]);


        // Iteration 3
        $cryptograms = $this->getFa([
            'email' => ['email' => 'foo@example.org'],
        ]);

        $this
            ->post(
                '/api/auth/register',
                ['cryptograms_fa' => $cryptograms, 'first_name' => 'Иван', 'device' => 'foo', 'password' => '12345678', 'role' => 'admin']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => true]]);


        $this->assertSame($maxUserId, (int) \App\User::withTrashed()->max('id'));
    }

    /**
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('yesNo')]
    public function test_login_success(bool $yesNo)
    {
        \Date::setTestNow('2025-08-19 17:00:00');
        $user = \App\User::factory()->hasRole(['is_customer' => true])->create(['phone' => '79000000001', 'email' => $yesNo ? 'foo@example.org' : null, 'totp_secret' => null]);
        $cryptograms = array_filter([
            $yesNo ? encrypt(new \AnourValar\EloquentNotification\FaMapper('phone', ['phone' => '79000000001'])) : null,
            encrypt(new \AnourValar\EloquentNotification\FaMapper('password', ['id' => $user->id, 'phone' => '79000000001'])),
        ]);

        $token1 = $user->createToken('foo', ['*'], \Date::parse('2025-08-19 16:00:00'));
        $token2 = $user->createToken('foo', ['*'], \Date::parse('2025-08-19 18:00:00'));

        $token = $this
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.3'])
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms, 'device' => 'bar']
            )
            ->assertStatus(200)
            ->assertJson(['token' => true])
            ->json('token');

        $this->assertNull($user->tokens()->where('id', '=', $token1->accessToken->id)->first());
        $this->assertNotNull($user->tokens()->where('id', '=', $token2->accessToken->id)->first());

        $this->get('/api/auth/state', ['Authorization' => "Bearer {$token}"])->assertStatus(200);
        $this->assertSame(2, $user->tokens()->count());
        $this->assertSame(
            [
                'name' => 'bar',
                'abilities' => ['*'],
                'expires_at' => '2026-08-19T17:00:00.000000Z',
            ],
            json_decode(json_encode($user->tokens()->withCasts(config('models.user.token_casts'))->latest('id')->first()->only('name', 'abilities', 'expires_at')), true)
        );

        $this->assertSame(
            [
                'user_id' => $user->id,
                'type' => 'metric',
                'event' => 'user_token_obtain',
                'ip_address' => '127.0.0.3',
            ],
            \App\Journal::latest('id')->select(['user_id', 'type', 'event', 'ip_address'])->first()->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_login_failure_deleted()
    {
        \App\User::withTrashed()->forceDelete();
        $user = \App\User::factory()->hasAbility('cabinet')->create(['phone' => '79000000001']);
        $token = $user->createToken('foo')->plainTextToken;
        $user->delete();

        $cryptograms = $this->getFa([
            'phone' => ['phone' => '79000000001'],
            'password' => ['id' => $user->id, 'phone' => '79000000001'],
        ]);

        $this
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms, 'device' => 'foo']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => [trans('controllers/api/auth.createToken_deleted')]]]);

        $this->get('/api/auth/state', ['Authorization' => "Bearer {$token}"])->assertStatus(401);
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * @return void
     */
    public function test_login_failure_not_exists()
    {
        $cryptograms = $this->getFa([
            'foo' => ['phone' => '79000000001'],
        ]);

        $this
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms, 'device' => 'baz']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => [trans('controllers/api/auth.createToken_not_exists')]]]);
    }

    /**
     * @return void
     */
    public function test_login_failure_validation()
    {
        \App\User::withTrashed()->forceDelete();
        $user = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]), 'phone' => '79000000001']);
        $cryptograms = $this->getFa([
            'phone' => ['phone' => '79000000001'],
            'password' => ['id' => $user->id, 'phone' => '79000000001'],
        ]);


        // Iteration 1
        $this
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['device' => true]]);


        // Iteration 2
        $this
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms, 'device' => ['bar']]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['device' => true]]);


        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * @return void
     */
    public function test_login_failure_acl_1()
    {
        \Date::setTestNow('2025-08-19 17:00:00');
        \App\User::withTrashed()->forceDelete();
        $user = \App\User::factory()->totpFixed()->hasAbility('cabinet')->create(['phone' => '79000000001', 'email' => 'foo@example.org']);

        // Iteration 1
        $cryptograms = $this->getFa([
            'phone' => ['phone' => '79000000001'],
        ]);

        $this
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms, 'device' => 'bar']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['Confirmation of factors required: 2']]]);


        // Iteration 2
        $cryptograms = $this->getFa([
            'phone' => ['id' => $user->id, 'phone' => '79000000001'],
            'session' => ['id' => $user->id],
        ]);

        $this
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms, 'device' => 'bar']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['Request error.']]]);


        // Iteration 3
        $this
            ->post(
                '/api/auth/login',
                ['device' => 'bar']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => ['Confirmation of factors required: 1-5']]]);


        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * @return void
     */
    public function test_login_failure_acl_2()
    {
        \Date::setTestNow('2025-08-19 17:00:00');
        \App\User::withTrashed()->forceDelete();
        $user = \App\User::factory()->create(['phone' => '79000000001', 'email' => null, 'additional_abilities' => null, 'totp_secret' => null]);
        config(["models.user.role.{$user->role}.abilities" => []]);

        $cryptograms = $this->getFa([
            'foo' => ['phone' => '79000000001'],
        ]);

        $this
            ->post(
                '/api/auth/login',
                ['cryptograms_fa' => $cryptograms, 'device' => 'bar']
            )
            ->assertStatus(403)
            ->assertJson(['errors' => ['error' => [trans('controllers/api/auth.createToken_not_exists')]]]);


        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * @return void
     */
    public function test_state_success()
    {
        $user = $this->authUser('cabinet', ['additional_abilities' => 'foo']);
        config(["models.user.role.{$user->role}.abilities" => ['cabinet.user.read', 'admin.administration']]);

        $response = $this
            ->withHeader('Accept-language', 'ru')
            ->get('/api/auth/state')
            ->assertStatus(200)
            ->assertDontSee('models\/')
            ->assertDontSee('entities\/');

        $this->compareFactors($user, $response);

        $response->assertExactJson([ // отражение в swagger
                'current_token_id' => $user->currentAccessToken()->id,
                'current_token_abilities' => $user->currentAccessToken()->abilities,

                'user' => \App\User::heavy()->find($user->id)->toArray(),
                'user_roles' => [
                    'admin' => ['title' => 'Администратор'],
                    'staff' => ['title' => 'Сотрудник'],
                    'client' => ['title' => 'Клиент'],
                ],
                'user_factors' => $response['user_factors'],

                'current_abilities' => ['cabinet.user.read', 'admin.administration', 'foo'],

                'user_notification_triggers' => [
                    'user_email_updated' => ['title' => 'Изменён email', 'channels' => ['sms', 'telegram', 'mail', 'push', 'database'], 'optgroup' => 'Профиль', 'ui' => null],
                    'user_phone_updated' => ['title' => 'Изменён телефон', 'channels' => ['sms', 'telegram', 'mail', 'push', 'database'], 'optgroup' => 'Профиль', 'ui' => null],
                    'user_password_updated' => ['title' => 'Изменён пароль', 'channels' => ['sms', 'telegram', 'mail', 'push', 'database'], 'optgroup' => 'Профиль', 'ui' => null],
                    'personal_message' => ['title' => 'Персональное уведомление', 'channels' => ['database'], 'optgroup' => 'Профиль', 'ui' => 'readonly'],
                    'user_logged_in_unique_ip' => ['title' => 'Вход с нового IP', 'channels' => ['sms', 'telegram', 'mail', 'push', 'database'], 'optgroup' => 'Безопасность', 'ui' => null],
                ],
                'user_notification_channels' => [
                    'database' => 'ЛК',
                    'mail' => 'Email',
                    'sms' => 'SMS',
                    'push' => 'Push',
                    'telegram' => 'Telegram',
                ],

                'field_statuses' => [
                    'draft' => ['title' => 'Черновик'],
                    'moderation' => ['title' => 'Модерация'],
                    'verified' => ['title' => 'Верифицирован'],
                ],

                'directory_types' => [
                    'user_tag' => ['title' => 'Тег пользователя', 'is_manually' => true],
                ],

                'ticket_types' => [
                    'user_moderate' => [
                        'title' => 'Модерация пользователя',
                        'options' => [
                            'fields' => [
                                'profile_form', 'profile_full_name', 'profile_company', 'profile_ru_inn', 'profile_ru_kpp',
                                'profile_passport_document',
                            ],
                        ],
                    ],
                    'user_request' => ['title' => 'Пользовательский запрос', 'options' => []],
                    'guest_request' => ['title' => 'Гостевой запрос', 'options' => []],
                ],
                'ticket_statuses' => [
                    'new' => ['title' => 'Новый', 'ui' => 'success'],
                    'in_work' => ['title' => 'В работе', 'ui' => 'primary'],
                    'approve' => ['title' => 'Выполнен', 'ui' => 'secondary'],
                    'reject' => ['title' => 'Отклонён', 'ui' => 'danger'],
                ],

                'journal_entities' => [
                    //'directory' => ['title' => 'Справочник'],
                    'field' => ['title' => 'Поле'],
                    'setting' => ['title' => 'Настройки'],
                    'ticket' => ['title' => 'Тикет'],
                    'user' => ['title' => 'Пользователь'],
                ],
                'journal_events' => [
                    // Model
                    'create' => ['title' => 'Создание', 'optgroup' => 'Модель'],
                    'update' => ['title' => 'Изменение', 'optgroup' => 'Модель'],
                    'delete' => ['title' => 'Удаление', 'optgroup' => 'Модель'],
                    'restore' => ['title' => 'Восстановление', 'optgroup' => 'Модель'],

                    // Integration
                    //'mts_sms_send' => ['title' => 'МТС (отправка смс)', 'optgroup' => 'Интеграция'],

                    // Metric
                    'user_session_obtain' => ['title' => 'Аутентификация (сессия)', 'optgroup' => 'Метрика'],
                    'user_token_obtain' => ['title' => 'Аутентификация (токен)', 'optgroup' => 'Метрика'],
                    'user_token_delete' => ['title' => 'Удаление токена', 'optgroup' => 'Метрика'],
                ],

                'telegram_bot_name' => 'NotificationBot',
                'number_multiple' => 100,
            ]);
    }

    /**
     * @return void
     */
    public function test_state_failure_deleted()
    {
        $user = \App\User::factory()->create();
        $token = $user->createToken('foo')->plainTextToken;
        $user->delete();

        $this
            ->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('/api/auth/state')
            ->assertStatus(401)
            ->assertJson(['message' => true, 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_state_failure_not_auth()
    {
        $this
            ->get('/api/auth/state')
            ->assertStatus(401)
            ->assertJson(['message' => true, 'errors' => []]);
    }

    /**
     * @param \App\User $user
     * @param \Illuminate\Testing\TestResponse $response
     */
    private function compareFactors(\App\User $user, \Illuminate\Testing\TestResponse $response): void
    {
        $factors = \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->fa($user);
        if (isset($factors['phone'])) {
            $factors['phone']['value'] = decrypt($factors['phone']['value']);
        }
        if (isset($factors['email'])) {
            $factors['email']['value'] = decrypt($factors['email']['value']);
        }

        $response = $response->json();
        $response = isset($response['factors']) ? $response['factors'] : $response['user_factors'];
        if (isset($response['phone'])) {
            $response['phone']['value'] = decrypt($response['phone']['value']);
        }
        if (isset($response['email'])) {
            $response['email']['value'] = decrypt($response['email']['value']);
        }

        $this->assertSame($factors, $response);
    }
}
