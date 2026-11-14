<?php

namespace Database\Seeders\Fake;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;

class DirectoryPersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $referenceUserId = \App\User::whereIn('role', \ConfigHelper::keys('models.user.role', ['is_customer' => true]))->min('id');

        foreach (\ConfigHelper::keys('models.directory.type') as $type) {
            if (\ConfigHelper::hasItem("models.directory.type.{$type}.rules.user_id", 'prohibited')) {
                continue;
            }

            // Reference
            \App\Directory::factory()
                ->count(ceil(50 * DatabaseSeeder::FAKE_COEF))
                ->make(['user_id' => $referenceUserId, 'type' => $type])
                ->each([$this, 'fillDirectory']);

            // "Массовка"
            \App\Directory::factory()
                ->count(ceil(20 * DatabaseSeeder::FAKE_COEF))
                ->existingUser($referenceUserId)
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
