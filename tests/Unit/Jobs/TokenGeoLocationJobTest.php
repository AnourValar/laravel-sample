<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;

class TokenGeoLocationJobTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_handle_success_1()
    {
        $user = \App\User::factory()->create();
        $token1 = $user->createToken('foo')->accessToken;
        $token2 = $user->createToken('foo')->accessToken;

        $this->assertNull($token1->refresh()->geo_location);
        $this->assertNull($token2->refresh()->geo_location);

        $this->partialMock(\App\Adapters\GeoLocation\GeoLocationInterface::class, function ($mock) {
            $mock->shouldReceive('fetch')->once()->andReturn('Russia, Moscow');
        });

        dispatch(new \App\Jobs\TokenGeoLocationJob($token1->id, '127.0.0.1'));

        $this->assertSame('Russia, Moscow', $token1->refresh()->geo_location);
        $this->assertNull($token2->refresh()->geo_location);
    }

    /**
     * @return void
     */
    public function test_handle_success_2()
    {
        $user = \App\User::factory()->create();
        $token = $user->createToken('foo')->accessToken;

        $this->assertNull($token->refresh()->geo_location);

        $this->partialMock(\App\Adapters\GeoLocation\GeoLocationInterface::class, function ($mock) {
            $mock->shouldReceive('fetch')->once()->andReturnNull();
        });

        dispatch(new \App\Jobs\TokenGeoLocationJob($token->id, '127.0.0.1'));

        $this->assertNull($token->refresh()->geo_location);
    }

    /**
     * @return void
     */
    public function test_handle_failure()
    {
        $user = \App\User::factory()->create();
        $token = $user->createToken('foo')->accessToken;

        $this->assertNull($token->refresh()->geo_location);

        $this->partialMock(\App\Adapters\GeoLocation\GeoLocationInterface::class, function ($mock) {
            $mock->shouldReceive('fetch')->once()->andThrow(new \App\Exceptions\ExternalException('foo'));
        });

        $this->expectException(\App\Exceptions\ExternalException::class);
        try {
            dispatch(new \App\Jobs\TokenGeoLocationJob($token->id, '127.0.0.1'));
        } catch (\App\Exceptions\ExternalException $e) {
            $this->assertNull($token->refresh()->geo_location);
            throw $e;
        }
    }
}
