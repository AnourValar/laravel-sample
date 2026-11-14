<?php

namespace App\Console\Commands\Demo;

use Illuminate\Console\Command;

class TriggerNotificationCommand extends Command
{
    use \Illuminate\Console\ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:trigger-notification {--only=} {--channels=mail}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger notification(s)';

    /**
     * @example foreach (DB::table('notifications')->get() as $item) { dump(json_decode($item->data), true); }
     *
     * Execute the console command.
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $only = $this->option('only');
        $channels = explode(',', $this->option('channels'));

        $user = \App\User::whereIn('role', \ConfigHelper::keys('models.user.role', ['is_customer' => true]))->orderBy('id', 'ASC')->first();
        $params = [
            \App\Ticket::class => \App\Ticket::factory()->make(['author_id' => $user->id]),
            \App\Journal::class => \App\Journal::factory()->make(['user_id' => $user->id]),
            'message' => 'Демо-сообщение.',
        ];

        $user->tokens()->delete();
        $user->createToken('foo')->accessToken->forceFill(['push_token' => 'token-' . time()])->save();

        $filtered = [];
        \App\UserNotification::where('user_id', '=', $user->id)->delete();
        foreach (config('eloquent_notification.trigger') as $trigger => $details) {
            if ($only && $trigger != $only) {
                continue;
            }

            $triggerChannels = array_values(array_intersect($channels, $details['channels']));
            if (! $triggerChannels) {
                continue;
            }

            \App\UserNotification::factory()->make(['user_id' => $user->id, 'trigger' => $trigger, 'channels' => $triggerChannels])->validate()->save();
            $filtered[] = $trigger;
        }
        \Cache::memo()->clear();

        foreach (config('eloquent_notification.trigger') as $trigger => $details) {
            if (in_array($trigger, $filtered)) {
                $this->triggerNotification($user, $params, $details);
                $this->info("{$trigger} sent.");
            } else {
                $this->warn("{$trigger} skipped.");
            }
        }
    }

    /**
     * @param \App\User $user
     * @param array $params
     * @param array $details
     * @return void
     */
    private function triggerNotification(\App\User $user, array $params, array $details): void
    {
        $class = $details['bind'];
        $args = [];

        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        foreach (($constructor?->getParameters() ?? []) as $param) {
            //$name = $param->getName();
            $type = $param->getType()->getName();
            if ($param->getType()->isBuiltin()) {
                $type = $param->getName();
            }

            $args[] = $params[$type];
        }

        $user->notify(new $class(...$args));
    }
}
