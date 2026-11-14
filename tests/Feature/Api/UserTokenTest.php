<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class UserTokenTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_token_index_success()
    {
        $user = $this->authUser('cabinet');

        $userToken1 = $user->createToken('foo');
        $userToken2 = $user->createToken('bar');

        $this
            ->get('/api/user/token/index')
            ->assertStatus(200)
            ->assertExactJson(['data' => [
                $userToken1->accessToken->only(config('models.user.token_light')),
                $userToken2->accessToken->only(config('models.user.token_light')),
            ]]);
    }

    /**
     * @return void
     */
    public function test_token_index_failure_not_auth()
    {
        $this->get('/api/user/token/index')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_token_index_failure_acl()
    {
        $user = $this->authUser('cabinet');

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->get('/api/user/token/index')->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_token_delete_success()
    {
        $user = $this->authUser('cabinet');
        $userToken1 = $user->createToken('foo');
        $userToken2 = $user->createToken('bar');

        $this
            ->post('/api/user/token/delete/' . $userToken1->accessToken->id, [])
            ->assertStatus(200)
            ->assertJson(['user_token' => ['id' => $userToken1->accessToken->id]]);

        $this->assertSame(
            [$userToken2->accessToken->id],
            $user->tokens()->get()->pluck('id')->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_token_delete_failure_not_auth()
    {
        $this->post('/api/user/token/delete/1')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_token_delete_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => $user->role]);
        $userToken = $user2->createToken('foo');

        $this->post('/api/user/token/delete/' . $userToken->accessToken->id)->assertStatus(404);
        $this->post('/api/user/token/delete/' . ($userToken->accessToken->id + 100))->assertStatus(404);

        $this->assertNotNull($userToken->accessToken->fresh());
    }

    /**
     * @return void
     */
    public function test_token_delete_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $userToken = $user->createToken('foo');

        $this->post('/api/user/token/delete/' . $userToken->accessToken->id)->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_token_delete_except_success()
    {
        $user = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => $user->role]);
        $userToken1 = $user->createToken('foo')->accessToken;
        $userToken2 = $user->createToken('bar')->accessToken;
        $userToken3 = $user->createToken('baz')->accessToken;
        $userToken4 = $user->createToken('foobar', ['hello'])->accessToken;
        $userToken5 = $user2->createToken('foo-2')->accessToken;


        // Iteration 1
        $this
            ->post('/api/user/token/delete_except/' . $userToken1->id, [])
            ->assertStatus(200)
            ->assertJson(['user_token' => ['ids' => [$userToken2->id, $userToken3->id]]]);

        $this->assertNotNull($userToken1->fresh());
        $this->assertNull($userToken2->fresh());
        $this->assertNull($userToken3->fresh());
        $this->assertNotNull($userToken4->fresh());
        $this->assertNotNull($userToken5->fresh());


        // Iteration 2
        $this
            ->post('/api/user/token/delete_except/' . '0', [])
            ->assertStatus(200)
            ->assertJson(['user_token' => ['ids' => [$userToken1->id]]]);

        $this->assertNull($userToken1->fresh());
        $this->assertNull($userToken2->fresh());
        $this->assertNull($userToken3->fresh());
        $this->assertNotNull($userToken4->fresh());
        $this->assertNotNull($userToken5->fresh());


        // Iteration 3
        $this
            ->post('/api/user/token/delete_except/' . '0', [])
            ->assertStatus(200)
            ->assertJson(['user_token' => ['ids' => []]]);

        $this->assertNull($userToken1->fresh());
        $this->assertNull($userToken2->fresh());
        $this->assertNull($userToken3->fresh());
        $this->assertNotNull($userToken4->fresh());
        $this->assertNotNull($userToken5->fresh());
    }

    /**
     * @return void
     */
    public function test_token_delete_except_failure_not_auth()
    {
        $this->post('/api/user/token/delete_except/1')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_token_delete_except_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $userToken = $user->createToken('foo')->accessToken;

        $this->post('/api/user/token/delete_except/' . $userToken->id)->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_token_push_success()
    {
        $user = $this->authUser('cabinet');
        $userToken1 = $user->createToken('foo')->accessToken;
        $userToken2 = $user->createToken('bar')->accessToken;


        // Iteration 1
        $this
            ->post('/api/user/token/push/' . $userToken1->id, ['push_token' => 'foo'])
            ->assertStatus(200)
            ->assertJson(['user_token' => ['id' => $userToken1->id]]);

        $this->assertSame('foo', $userToken1->refresh()->push_token);
        $this->assertNull($userToken2->refresh()->push_token);


        // Iteration 2
        $userToken2->forceFill(['push_token' => 'bar'])->save();

        $this
            ->post('/api/user/token/push/' . $userToken1->id, ['push_token' => null])
            ->assertStatus(200)
            ->assertJson(['user_token' => ['id' => $userToken1->id]]);

        $this->assertNull($userToken1->refresh()->push_token);
        $this->assertSame('bar', $userToken2->refresh()->push_token);
    }

    /**
     * @return void
     */
    public function test_token_push_failure_not_auth()
    {
        $this->post('/api/user/token/push/1')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_token_push_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => $user->role]);
        $userToken = $user2->createToken('foo')->accessToken;

        $this->post('/api/user/token/push/' . $userToken->id)->assertStatus(404);
        $this->post('/api/user/token/push/' . ($userToken->id + 100))->assertStatus(404);

        $this->assertNull($userToken->refresh()->push_token);
    }

    /**
     * @return void
     */
    public function test_token_push_failure_validation()
    {
        $user = $this->authUser('cabinet');
        $userToken = tap($user->createToken('foo')->accessToken->forceFill(['push_token' => 'foo']))->save();

        $this->post('/api/user/token/push/' . $userToken->id, ['push_token' => '0'])->assertStatus(422)->assertJson(['errors' => ['push_token' => true]]);
        $this->post('/api/user/token/push/' . $userToken->id, ['push_token' => ['bar']])->assertStatus(422)->assertJson(['errors' => ['push_token' => true]]);

        $this->assertSame('foo', $userToken->refresh()->push_token);
    }

    /**
     * @return void
     */
    public function test_token_push_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $userToken = $user->createToken('foo')->accessToken;

        $this->post('/api/user/token/push/' . $userToken->id)->assertStatus(403);
    }
}
