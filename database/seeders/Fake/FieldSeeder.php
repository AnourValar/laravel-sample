<?php

namespace Database\Seeders\Fake;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;

class FieldSeeder extends Seeder
{
    use \AnourValar\EloquentFile\Traits\SeederTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $referenceUserId = \App\User::whereIn('role', \ConfigHelper::keys('models.user.role', ['is_customer' => true]))->min('id');

        foreach (\ConfigHelper::keys('models.field.name') as $name) {
            // Reference
            \App\Field::factory()
                ->count(1)
                ->make(['user_id' => $referenceUserId, 'name' => $name, 'status' => (new \App\Field())->status])
                ->each([$this, 'fillField']);

            // "Массовка"
            \App\Field::factory()
                ->count(1)
                ->existingUser($referenceUserId)
                ->make(['name' => $name])
                ->each([$this, 'fillField']);
        }
    }

    /**
     * @param \App\Field $field
     * @return void
     */
    public function fillField(\App\Field $field): void
    {
        // Сохранение
        $field->validate()->save();

        // Файлы
        foreach ($field->name_details['file_virtuals'] as $name) {
            $fileVirtual = (new \App\FileVirtual())->forceFill(['name' => $name]);
            $this->createFromList($fileVirtual, $field, storage_path('app/fake/pdf/'));
        }
    }
}
