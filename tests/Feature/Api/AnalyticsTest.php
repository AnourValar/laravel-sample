<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_index_success()
    {
        \Date::setTestNow('2025-09-02 15:00:00');
        $user = $this->authUser('cabinet.user.read');
        $data = $this->getReferenceData($user);

        foreach (\Illuminate\Database\Eloquent\Relations\Relation::morphMap() as $class) {
            if (! in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses($class))) {
                continue;
            }
            $class::factory()->count(5)->create();
        }

        // Iteration 1
        $referenceData = [];
        foreach (config('entities.analytics.type') as $type => $config) {
            $response = $this
                ->withHeader('Accept-Language', 'ru')
                ->post("/api/analytics/{$type}", $data)
                ->assertStatus(200);

            $referenceData[$type] = \App::make($config['bind'])->getData($data);
            if (is_object($referenceData[$type])) {
                $referenceData[$type] = $referenceData[$type]->toArray();
            }
            $response->assertExactJson($referenceData[$type]);
        }

        // Iteration 2 (проверка кэша)
        foreach (\Illuminate\Database\Eloquent\Relations\Relation::morphMap() as $class) {
            if (! in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses($class))) {
                continue;
            }
            if (config('auth.providers.users.model') == $class) {
                continue;
            }
            $class::query()->forceDelete();
        }

        foreach (\ConfigHelper::keys('entities.analytics.type') as $type) {
            $this
                ->withHeader('Accept-Language', 'ru')
                ->post("/api/analytics/{$type}", $data)
                ->assertStatus(200)
                ->assertExactJson($referenceData[$type]);
        }
    }

    /**
     * @return void
     */
    public function test_index_success_empty()
    {
        \Date::setTestNow('2025-09-02 15:00:00');
        foreach (\Illuminate\Database\Eloquent\Relations\Relation::morphMap() as $class) {
            if (! in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses($class))) {
                continue;
            }
            $class::query()->forceDelete();
        }

        $user = $this->authUser('cabinet.user.read');
        $data = $this->getReferenceData($user);

        foreach (config('entities.analytics.type') as $type => $config) {
            $response = $this
                ->withHeader('Accept-Language', 'ru')
                ->post("/api/analytics/{$type}", $data)
                ->assertStatus(200);

            $referenceData[$type] = \App::make($config['bind'])->getData($data);
            if (is_object($referenceData[$type])) {
                $referenceData[$type] = $referenceData[$type]->toArray();
            }
            $response->assertExactJson($referenceData[$type]);
        }
    }

    /**
     * @return void
     */
    public function test_index_failure_validation()
    {
        $this->authUser('cabinet.user.read');

        $hasError = false;
        foreach (\ConfigHelper::keys('entities.analytics.type') as $type) {
            $statusCode = $this
                ->withHeader('Accept-Language', 'ru')
                ->post("/api/analytics/{$type}", ['foo' => 'bar'])
                ->getStatusCode();

            if ($statusCode == 422) {
                $hasError = true;
            }
        }

        $this->assertTrue($hasError);
    }

    /**
     * @return void
     */
    public function test_index_failure_not_auth()
    {
        $data = $this->getReferenceData(null);

        foreach (\ConfigHelper::keys('entities.analytics.type') as $type) {
            $statusCode = $this
                ->withHeader('Accept-Language', 'ru')
                ->post("/api/analytics/{$type}", $data)
                ->getStatusCode();

            $this->assertTrue(in_array($statusCode, [200, 401, 403, 422]));
        }
    }

    /**
     * @return void
     */
    public function test_index_failure_authorize()
    {
        $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $other = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $data = $this->getReferenceData($other);

        foreach (\ConfigHelper::keys('entities.analytics.type') as $type) {
            $statusCode = $this
                ->withHeader('Accept-Language', 'ru')
                ->post("/api/analytics/{$type}", $data)
                ->getStatusCode();

            $this->assertTrue(in_array($statusCode, [200, 403]));
        }
    }

    /**
     * @return void
     */
    public function test_index_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => []]);
        $data = $this->getReferenceData($user);

        foreach (\ConfigHelper::keys('entities.analytics.type') as $type) {
            $statusCode = $this
                ->withHeader('Accept-Language', 'ru')
                ->post("/api/analytics/{$type}", $data)
                ->getStatusCode();

            $this->assertTrue(in_array($statusCode, [200, 403]));
        }
    }

    /**
     * @return void
     */
    public function test_index_failure_not_found()
    {
        $this->authUser('cabinet.user.read');

        $this
            ->post('/api/analytics/foo', [])
            ->assertStatus(404);
    }

    /**
     * @param \App\User|null $user
     * @return array
     */
    private function getReferenceData(?\App\User $user): array
    {
        return [
            'user_id' => $user?->id,
            'page' => 1,
            'per_page' => 20,
            'sort_column' => 'id',
            'sort_direction' => 'ASC',
        ];
    }
}
