<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_obtainToken_1()
    {
        \Atom::transactionZeroLevel(1);
        \Queue::fake();
        \Date::setTestNow('2025-11-27 11:00:00');
        $service = \App::make(\App\Services\UserService::class);
        $user = \App\User::factory()->create();

        \App::make(\Illuminate\Http\Request::class)->server->add(['REMOTE_ADDR' => null]);
        $token = $service->obtainToken($user, 'foo');
        $this->assertSame(
            [
                'id' => $token->accessToken->id,
                'name' => 'foo',
                'abilities' => ['*'],
                'ip_address' => null,
                'geo_location' => null,
                'last_used_at' => null,
                'created_at' => '2025-11-27T11:00:00.000000Z',
                'expires_at' => '2026-11-27T11:00:00.000000Z',
            ],
            json_decode(json_encode($token->accessToken->refresh()->only(array_merge(config('models.user.token_light'), ['expires_at']))), true)
        );

        \Queue::assertPushed(\App\Jobs\TokenGeoLocationJob::class, 0);
    }

    /**
     * @return void
     */
    public function test_obtainToken_2()
    {
        \Atom::transactionZeroLevel(1);
        \Queue::fake();
        \Date::setTestNow('2025-11-27 11:00:00');
        $service = \App::make(\App\Services\UserService::class);
        $user = \App\User::factory()->create();

        \App::make(\Illuminate\Http\Request::class)->server->add(['REMOTE_ADDR' => '127.0.0.2']);
        $token = $service->obtainToken($user, 'foo');
        $this->assertSame(
            [
                'id' => $token->accessToken->id,
                'name' => 'foo',
                'abilities' => ['*'],
                'ip_address' => '127.0.0.2',
                'geo_location' => null,
                'last_used_at' => null,
                'created_at' => '2025-11-27T11:00:00.000000Z',
                'expires_at' => '2026-11-27T11:00:00.000000Z',
            ],
            json_decode(json_encode($token->accessToken->refresh()->only(array_merge(config('models.user.token_light'), ['expires_at']))), true)
        );

        \Queue::assertPushed(\App\Jobs\TokenGeoLocationJob::class, 1);
        \Queue::assertPushed(\App\Jobs\TokenGeoLocationJob::class, fn ($job) => $job->personalAccessTokenId == $token->accessToken->id && $job->ipAddress == '127.0.0.2');
    }

    /**
     * @return void
     */
    public function test_obtainToken_3()
    {
        \Atom::transactionZeroLevel(1);
        \Queue::fake();
        \Date::setTestNow('2025-11-27 11:00:00');
        $service = \App::make(\App\Services\UserService::class);
        $user = \App\User::factory()->create();

        \App::make(\Illuminate\Http\Request::class)->server->add(['REMOTE_ADDR' => '127.0.0.3']);
        $token = $service->obtainToken($user, 'foo', ['bar']);
        $this->assertSame(
            [
                'id' => $token->accessToken->id,
                'name' => 'foo',
                'abilities' => ['bar'],
                'ip_address' => '127.0.0.3',
                'geo_location' => null,
                'last_used_at' => null,
                'created_at' => '2025-11-27T11:00:00.000000Z',
                'expires_at' => null,
            ],
            json_decode(json_encode($token->accessToken->refresh()->only(array_merge(config('models.user.token_light'), ['expires_at']))), true)
        );

        \Queue::assertPushed(\App\Jobs\TokenGeoLocationJob::class, 1);
        \Queue::assertPushed(\App\Jobs\TokenGeoLocationJob::class, fn ($job) => $job->personalAccessTokenId == $token->accessToken->id && $job->ipAddress == '127.0.0.3');
    }
}
