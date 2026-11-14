<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * @var int
     */
    public const FAKE_COEF = 1;

    /**
     * Список сидов
     *
     * @var array
     */
    private $seeds = [
        \Database\Seeders\Present\SettingSeeder::class,
        \Database\Seeders\Present\DirectorySeeder::class,

        \Database\Seeders\Fake\DirectoryCommonSeeder::class => ['local', 'dev'],
        \Database\Seeders\Fake\UserSeeder::class => ['local', 'dev'],
        \Database\Seeders\Fake\FieldSeeder::class => ['local', 'dev'],
        \Database\Seeders\Fake\DirectoryPersonalSeeder::class => ['local', 'dev'],
        \Database\Seeders\Fake\TicketSeeder::class => ['local', 'dev'],
    ];

    /**
     * Список команд
     *
     * @var array
     */
    private $commands = [
        'model:validate' => ['local', 'dev'],

        //'app:payment-send' => ['local', 'dev'],

        'model:prune --path=app' => ['local', 'dev'],
        'eloquent_file:on-zero --days=10' => ['local', 'dev'],
        'schedule:run' => ['local', 'dev'],
    ];

    /**
     * Замещение сервис контейнера
     *
     * @var array
     */
    private $serviceContainerReplace = [
        'cache.store' => null,

        \AnourValar\EloquentNotification\Adapters\Telegram\TelegramInterface::class => \AnourValar\EloquentNotification\Adapters\Telegram\MockAdapter::class,
        \AnourValar\EloquentNotification\Adapters\Sms\SmsInterface::class => \AnourValar\EloquentNotification\Adapters\Sms\MockAdapter::class,
        \AnourValar\EloquentNotification\Adapters\Exchanger\ExchangerInterface::class => \AnourValar\EloquentNotification\Adapters\Exchanger\NullAdapter::class,
        \AnourValar\EloquentNotification\Adapters\Push\PushInterface::class => \AnourValar\EloquentNotification\Adapters\Push\MockAdapter::class,

        \App\Adapters\GeoLocation\GeoLocationInterface::class => \App\Adapters\GeoLocation\MockAdapter::class,
    ];

    /**
     * Замещение конфига
     *
     * @var array
     */
    private $configReplace = [
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'mail.default' => 'array',
        'broadcasting.default' => 'null',
        'pulse.enabled' => false,
        'clockwork.enable' => false,
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Config state
        $this->outputConfig();

        // Service container
        if (config('app.env') != 'production') {
            $this->handleServiceContainer();
        }

        // Config
        if (config('app.env') != 'production') {
            $this->handleConfig();
        }

        // Database...
        try {
            \DB::beginTransaction();
            \Atom::transactionZeroLevel(1);

            // Seeds
            $this->handleSeeds();

            \DB::commit();
        } catch (\Throwable $e) {
            if (config('app.env') == 'production') {
                \DB::rollBack();
            } else {
                \DB::commit();
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                dump($e->validator->getData(), $e->validator->errors()->all());
            }

            throw $e;
        } finally {
            \Atom::transactionZeroLevel(0);
        }

        // Commands
        $this->handleCommands();
    }

    /**
     * @return void
     */
    private function outputConfig(): void
    {
        \Illuminate\Foundation\Console\AboutCommand::add('Business', [
            //
        ]);

        \Artisan::call('about', [], $this->command->getOutput());
        $this->command->newLine();
    }

    /**
     * @return void
     */
    private function handleServiceContainer(): void
    {
        foreach ($this->serviceContainerReplace as $interface => $implementation) {
            if (is_null($implementation)) {
                \App::forgetInstance($interface);
                continue;
            }

            app()->bind($interface, function ($app, $arguments = []) use ($implementation) {
                return new $implementation(...$arguments);
            });
        }
    }

    /**
     * @return void
     * @throws \LogicException
     */
    private function handleConfig(): void
    {
        config($this->configReplace);

        if (! \App::make(\Illuminate\Cache\Repository::class)->getStore() instanceof \Illuminate\Cache\ArrayStore) {
            throw new \LogicException('Incorrect setup.');
        }
    }

    /**
     * @return void
     */
    private function handleSeeds(): void
    {
        $output = $this->command->getOutput();
        $this->command->setOutput(
            new class (new \Symfony\Component\Console\Input\ArrayInput([]), $this->command->getOutput()) extends \Illuminate\Console\OutputStyle
            {
                /**
                 * {@inheritdoc}
                 */
                public function writeln(\Traversable|array|string $messages, int $type = self::OUTPUT_NORMAL): void
                {
                    $messages = (array) $messages;

                    foreach ($messages as &$message) {
                        if (mb_strlen($message) && (mb_strlen($message) - mb_strlen(ltrim($message))) < 2) {
                            $message = str_pad('', 4, ' ', STR_PAD_LEFT) . $message;
                        }
                    }

                    parent::writeln($messages, $type);
                }
            }
        );

        foreach ($this->seeds as $key => $value) {
            if (is_numeric($key)) {
                $this->call($value);
            } elseif (in_array(config('app.env'), $value)) {
                $this->call($key);
            }
        }

        $this->command->setOutput($output);
    }

    /**
     * @return void
     */
    private function handleCommands(): void
    {
        $this->command->newLine();
        $components = new \Illuminate\Console\View\Components\Factory($this->command->getOutput());
        $components->info('Commands');

        foreach ($this->commands as $key => $value) {
            $name = null;
            if (is_numeric($key)) {
                $name = $value;
            } elseif (in_array(config('app.env'), $value)) {
                $name = $key;
            }

            if (isset($name)) {
                $this->command->getOutput()->writeln("<comment>Command executing:</comment> {$name}");
                $startTime = microtime(true);

                \Artisan::call($name, [], $this->command->getOutput());

                $runTime = round(microtime(true) - $startTime, 2);
                $this->command->getOutput()->writeln("<info>Command executed:</info>  {$name} ({$runTime} seconds)");
            }
        }
    }
}
