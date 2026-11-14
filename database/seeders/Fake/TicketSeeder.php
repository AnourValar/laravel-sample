<?php

namespace Database\Seeders\Fake;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    use \AnourValar\EloquentFile\Traits\SeederTrait;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Ticket::factory()
            ->count(ceil(200 * DatabaseSeeder::FAKE_COEF))
            ->existingAuthor()
            ->existingModerator()
            ->make()
            ->each([$this, 'fillTicket']);
    }

    /**
     * @param \App\Ticket $ticket
     * @return void
     */
    public function fillTicket(\App\Ticket $ticket): void
    {
        // Сохранение
        $ticket->validate()->save();

        // Файлы
        foreach (\ConfigHelper::keys('eloquent_file.file_virtual.entity.ticket.name') as $name) {
            if (! mt_rand(0, 1)) {
                continue;
            }

            $fileVirtual = (new \App\FileVirtual())->forceFill(['name' => $name]);
            $this->createFromList($fileVirtual, $ticket, storage_path(mt_rand(0, 1) ? 'app/fake/pdf/' : 'app/fake/image/'));
        }
    }
}
