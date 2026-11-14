<?php

namespace Tests;

use Database\Factories\UserFactory;

trait BusinessTrait
{
    use \AnourValar\EloquentFile\Traits\SeederTrait;

    /**
     * @usage #[\PHPUnit\Framework\Attributes\DataProvider('yesNo')]
     *
     * @return array
     */
    public static function yesNo(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @param string $ability
     * @param array $data
     * @param array $tokenAbilities
     * @return \App\User
     */
    protected function authUser(string $ability, array $data = [], array $tokenAbilities = ['*']): \App\User
    {
        if (! isset($data['additional_abilities'])) {
            $data['additional_abilities'] = null;
        }

        if (isset($data['totp_secret'])) {
            $data['totp_secret'] = \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->cryptogramTotp($data['totp_secret']);
        }

        $user = \App\User::factory()->hasAbility($ability)->create($data);
        \Laravel\Sanctum\Sanctum::actingAs($user, $tokenAbilities);

        return $user;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $name
     * @param array $files
     * @param bool $fake
     * @return \App\FileVirtual
     */
    protected function createFile(\Illuminate\Database\Eloquent\Model $model, string $name, array $files = ['pdf/01.pdf'], bool $fake = true): \App\FileVirtual
    {
        if ($fake) {
            $this->fakeStorages();
        }

        $fileVirtual = new \App\FileVirtual()->forceFill(['name' => $name]);
        $this->createFromList($fileVirtual, $model, storage_path('app/fake/'), $files);
        return $fileVirtual; // @phpstan-ignore return.type
    }

    /**
     * @param string $email
     * @param mixed $code
     * @return string
     */
    protected function getCryptogramEmail(string $email, &$code = null): string
    {
        $cryptogram = \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->requestEmail($email)['cryptogram_email'];
        $code = decrypt($cryptogram)['code'];

        return $cryptogram;
    }

    /**
     * @param string $phone
     * @param mixed $code
     * @return string
     */
    protected function getCryptogramPhone(string $phone, &$code = null): string
    {
        $cryptogram = \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->requestPhone($phone, 'string')['cryptogram_phone'];
        $code = decrypt($cryptogram)['code'];

        return $cryptogram;
    }

    /**
     * @param array $fa
     * @return array
     */
    protected function getFa($fa): array
    {
        $result = [];

        foreach ($fa as $name => $contacts) {
            $result[] = new \AnourValar\EloquentNotification\FaMapper($name, $contacts)->encrypt();
        }

        return $result;
    }

    /**
     * @param \App\User|null $user
     * @param array $data
     * @return \Illuminate\Notifications\DatabaseNotification
     */
    protected function createDatabaseNotification(?\App\User $user = null, array $data = []): \Illuminate\Notifications\DatabaseNotification
    {
        if (! $user) {
            $user = \App\User::factory()->create();
        }

        return \Illuminate\Notifications\DatabaseNotification::create(array_replace([
            'id' => fake()->uuid(),
            'type' => 'foo',
            'notifiable_type' => 'user',
            'notifiable_id' => $user->id,
            'data' => ['bar' => 'baz'],
            'read_at' => '2025-07-24 18:00:00',
            'created_at' => '2024-07-24 17:00:00',
        ], $data));
    }
}
