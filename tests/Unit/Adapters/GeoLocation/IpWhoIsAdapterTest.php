<?php

namespace Tests\Unit\Adapters\GeoLocation;

use Tests\TestCase;

class IpWhoIsAdapterTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_fetch_success()
    {
        $this->partialMock(\AnourValar\HttpClient\Http::class, function ($mock) {
            $response = [
                'success' => true,
                'country' => 'Netherlands',
                'region' => 'North Holland',
                'city' => 'Amsterdam',
            ];

            $mock
                ->shouldReceive('baseUrl')
                ->with('https://ipwho.is/')
                ->once()
                ->andReturnSelf();

            $mock
                ->shouldReceive('exec')
                ->with('127.0.0.1')
                ->once()
                ->andReturn(new \AnourValar\HttpClient\FakeResponse([], $response));
        });

        $this->assertSame('Netherlands, North Holland, Amsterdam', $this->getAdapter()->fetch('127.0.0.1'));
    }

    /**
     * @return void
     */
    public function test_fetch_failure_1()
    {
        $this->partialMock(\AnourValar\HttpClient\Http::class, function ($mock) {
            $response = [
                'success' => false,
                'message' => 'Invalid IP address',
            ];

            $mock
                ->shouldReceive('baseUrl')
                ->with('https://ipwho.is/')
                ->once()
                ->andReturnSelf();

            $mock
                ->shouldReceive('exec')
                ->with('127.0.0.2')
                ->once()
                ->andReturn(new \AnourValar\HttpClient\FakeResponse([], $response));
        });

        $this->assertNull($this->getAdapter()->fetch('127.0.0.2'));
    }

    /**
     * @return void
     */
    public function test_fetch_failure_2()
    {
        $this->partialMock(\AnourValar\HttpClient\Http::class, function ($mock) {
            $response = [
                'success' => false,
            ];

            $mock
                ->shouldReceive('baseUrl')
                ->with('https://ipwho.is/')
                ->once()
                ->andReturnSelf();

            $mock
                ->shouldReceive('exec')
                ->with('127.0.0.3')
                ->once()
                ->andReturn(new \AnourValar\HttpClient\FakeResponse([], $response));
        });

        $this->expectException(\App\Exceptions\ExternalException::class);
        $this->getAdapter()->fetch('127.0.0.3');
    }

    /**
     * @return \App\Adapters\GeoLocation\IpWhoIsAdapter
     */
    private function getAdapter(): \App\Adapters\GeoLocation\IpWhoIsAdapter
    {
        return \App::make(\App\Adapters\GeoLocation\IpWhoIsAdapter::class);
    }
}
