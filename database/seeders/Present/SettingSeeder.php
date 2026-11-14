<?php

namespace Database\Seeders\Present;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * @var array
     */
    private array $reference = [
        'user_demo' => [
            'value' => ['phone' => '', 'code' => '111111', 'enabled' => false],
            'force' => false, // принудительное сидирование
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [];
        $settings = \App\Setting::get();

        foreach ($this->reference as $name => $curr) {
            if ($settings->firstWhere('name', $name) && ! $curr['force']) {
                continue;
            }

            $data[$name] = $curr['value'];
        }

        if ($data) {
            \App::make(\App\Services\SettingService::class)->put($data);
        }
    }
}
