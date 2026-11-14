<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:admin {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create admin user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        \DB::transaction(function () {
            try {
                \Atom::lockUser();

                $data = [
                    'role' => \ConfigHelper::key('models.user.role', ['via_console' => true]),
                    'email' => $this->argument('email'),
                    'password' => \App::make(\App\Helpers\StringHelper::class)->hashPassword($this->argument('password')),
                    'first_name' => 'Администратор',
                ];

                $user = \App\User::query()
                    ->fields(array_keys($data))
                    ->fill($data)
                    ->validate()
                    ->create($data);

                event(new \Illuminate\Auth\Events\Registered($user));

                $this->info('Admin created successfully.');
            } catch (\Illuminate\Validation\ValidationException $e) {
                foreach ($e->validator->errors()->all() as $error) {
                    $this->error($error);
                }
            }
        });

        return Command::SUCCESS;
    }
}
