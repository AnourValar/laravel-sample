<?php

namespace App\Adapters\GeoLocation;

class MockAdapter implements GeoLocationInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Adapters\GeoLocation\GeoLocationInterface::fetch()
     */
    public function fetch(string $ip): ?string
    {
        if (! mt_rand(0, 10)) {
            return null;
        }

        $faker = \App::make(\Faker\Generator::class, ['locale' => 'en']);
        return implode(', ', [$faker->country(), $faker->city()]);
    }
}
