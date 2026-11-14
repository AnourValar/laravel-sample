<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_put_get()
    {
        \App\Setting::query()->delete();
        config(['models.setting.name' => [
            'aa' => ['bind' => \App\Handlers\Models\Setting\Name\UserDemoName::class],
            'bb' => ['bind' => \App\Handlers\Models\Setting\Name\UserDemoName::class],
            'cc' => ['bind' => \App\Handlers\Models\Setting\Name\UserDemoName::class],
        ]]);
        $service = \App::make(\App\Services\SettingService::class);

        // Iteration 1
        $service->put(['aa' => ['phone' => '79000000001', 'code' => 123456, 'enabled' => true]]);
        $this->assertSame(
            [
                'aa' => ['code' => '123456', 'phone' => '79000000001', 'enabled' => true],
            ],
            $service->get()
        );

        // Iteration 2
        $service->put(['bb' => ['phone' => 79000000002, 'code' => '123456', 'enabled' => true]]);
        $this->assertSame(
            [
                'aa' => ['code' => '123456', 'phone' => '79000000001', 'enabled' => true],
                'bb' => ['code' => '123456', 'phone' => '79000000002', 'enabled' => true],
            ],
            $service->get()
        );

        // Iteration 3 (cache)
        \App\Setting::query()->delete();
        $this->assertSame(
            [
                'aa' => ['code' => '123456', 'phone' => '79000000001', 'enabled' => true],
                'bb' => ['code' => '123456', 'phone' => '79000000002', 'enabled' => true],
            ],
            $service->get()
        );

        // Iteration 4 (specific key)
        $this->assertSame(['code' => '123456', 'phone' => '79000000001', 'enabled' => true], $service->get('aa'));
        $this->assertSame(['code' => '123456', 'phone' => '79000000002', 'enabled' => true], $service->get('bb'));

        // Iteration 5 (validation failure)
        $this->assertCustomValidationFailed(fn () => $service->put(['bb' => ['foo' => 'bar']]));
    }
}
