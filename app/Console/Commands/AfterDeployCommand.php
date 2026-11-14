<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

class AfterDeployCommand extends Command implements Isolatable
{
    use \AnourValar\LaravelAtom\Traits\PostgresTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'after:deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'After deploy updates';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // ...

        $this->info('Success');
        return Command::SUCCESS;
    }

    /**
     * @param array|string $migration
     * @return void
     */
    /*private function cleanMigration(array|string $migration): void
    {
        \DB::table('migrations')->whereIn('migration', (array) $migration)->delete();
    }*/
}
