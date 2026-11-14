<?php

namespace Database\Seeders\Present;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;

class DirectorySeeder extends Seeder
{
    use \AnourValar\LaravelAtom\Traits\PostgresTrait;

    /**
     * @var array
     */
    private array $directories = [
        //['type' => 'user_tag', 'data' => ['title' => 'Тег-1', 'color' => '#FFFFFF']],
    ];

    /**
     * @var array
     */
    private array $jobs = [
        //
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (\App\Directory::whereIn('type', array_column($this->directories, 'type'))->first()) {
            return;
        }

        foreach ($this->directories as $item) {
            \App\Directory::fields(array_keys($item))->fill($item)->validate()->save();
        }
        $this->syncAutoIncrement(new \App\Directory());

        foreach ($this->jobs as $job) {
            try {
                $job::dispatchSync();
            } catch (\App\Exceptions\ExternalException $e) {
                $e->report();
            }
        }
    }
}
