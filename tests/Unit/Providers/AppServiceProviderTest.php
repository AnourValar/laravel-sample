<?php

namespace Tests\Unit\Providers;

use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;

    /**
     * {@inheritDoc}
     * @see \Illuminate\Foundation\Testing\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['instance.phone.regexp' => ['/^7\d{10}$/']]);
    }

    /**
     * @return void
     */
    public function test_validate_rule_phone_success()
    {
        $phones = [
            '79991234567',
            '70000000000',
        ];

        foreach ($phones as $phone) {
            $validator = \Validator::make(['phone' => $phone], ['phone' => 'phone']);
            $this->assertTrue($validator->passes());
        }
    }

    /**
     * @return void
     */
    public function test_validate_rule_phone_failure()
    {
        $phones = [
            '89991234567', // wrong prefix
            '7999123456', // too short
            '799912345678', // too long
            '7ab9234567x', // letters
            '+7(999) 123-45-67', // not canonized
            ['foo'], // array
            'foo', // not a number
            null, // null
            1.5, // float
            true, // boolean
        ];

        foreach ($phones as $phone) {
            $validator = \Validator::make(['phone' => $phone], ['phone' => 'phone']);
            $this->assertTrue($validator->fails());
        }
    }
}
