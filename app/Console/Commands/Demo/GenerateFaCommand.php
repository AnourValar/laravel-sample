<?php

namespace App\Console\Commands\Demo;

use Illuminate\Console\Command;

class GenerateFaCommand extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:generate-fa {name} {--id=} {--email=} {--phone=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate FA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $name = $this->argument('name');
        $contacts = array_filter([
            'id' => $this->option('id') ? (int) $this->option('id') : null,
            'email' => $this->option('email'),
            'phone' => $this->option('phone'),
        ]);

        if (! $contacts) {
            $this->error('At least one contact should be provided');
            return COMMAND::FAILURE;
        }

        $faMapper = new \AnourValar\EloquentNotification\FaMapper($name, $contacts);
        $this->info("FA:\n{$faMapper}");
    }
}
