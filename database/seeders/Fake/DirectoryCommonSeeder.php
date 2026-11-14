<?php

namespace Database\Seeders\Fake;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;

class DirectoryCommonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (\ConfigHelper::keys('models.directory.type') as $type) {
            if (! \ConfigHelper::hasItem("models.directory.type.{$type}.rules.user_id", 'prohibited')) {
                continue;
            }

            if (\App\Directory::where('type', '=', $type)->first()) {
                continue;
            }

            \App\Directory::factory()
                ->count(ceil(50 * DatabaseSeeder::FAKE_COEF))
                ->make(['type' => $type])
                ->each([$this, 'fillDirectory']);
        }
    }

    /**
     * @param \App\Directory $directory
     * @return void
     */
    public function fillDirectory(\App\Directory $directory): void
    {
        // Сохранение
        $directory->validate()->save();
    }
}
