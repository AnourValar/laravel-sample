<?php

namespace Tests\Feature\Api;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return array
     */
    public static function provide_files(): array
    {
        return [
            [
                'name' => 'attachment_request',
                'path' => 'pdf/01.pdf',
                'closure' => function ($self) {
                    return [
                        $user = $self->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]),
                        \App\Ticket::factory()->create(['author_id' => $user->id, 'status' => new \App\Ticket()->status, 'created_at' => now()]),
                    ];
                },
                'title' => null,
            ],

            [
                'name' => 'attachment_response',
                'path' => 'image/01.jpg',
                'closure' => function ($self) {
                    return [
                        $user = $self->authUser('cabinet.ticket.update'),
                        \App\Ticket::factory()->create(['author_id' => $user->id, 'status' => new \App\Ticket()->status]),
                    ];
                },
                'title' => null,
            ],

            [
                'name' => 'attachment',
                'path' => 'pdf/01.pdf',
                'closure' => function ($self) {
                    return [
                        $user = $self->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]),
                        \App\Field::factory()->create(['user_id' => $user->id, 'name' => 'profile_passport_document', 'status' => 'draft']),
                    ];
                },
                'title' => null,
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable $closure
     * @param string|null $title
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_files')]
    public function test_upload_success(string $name, string $path, $closure, ?string $title)
    {
        $this->fakeStorages();
        $mime = mime_content_type(storage_path('app/fake/' . $path));
        [$user, $model] = $closure($this);
        $entity = $model->getMorphClass();

        $response = $this
            ->post(
                "/api/file/upload/{$entity}/{$model->id}/" . $name,
                ['file' => new UploadedFile(storage_path('app/fake/' . $path), basename($path), $mime, null, true), 'title' => $title]
            )
            ->assertStatus(200);

        $fileVirtual = \App\FileVirtual::latest('id')->first();
        $response->assertExactJson(['file_virtual' => ['id' => $fileVirtual->id]]);

        $this->assertSame(
            $ref = [
                'entity' => $entity,
                'entity_id' => $model->id,
                'name' => $name,
                'filename' => basename($path),
                'mime_type' => $mime,
                'title' => $title,
                'weight' => 0,
                'details' => null,
            ],
            $fileVirtual->load('filePhysical')->only(array_keys($ref))
        );
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable $closure
     * @param string|null $title
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_files')]
    public function test_upload_failure_not_auth(string $name, string $path, callable $closure, ?string $title)
    {
        $this->post("/api/file/upload/field/0/{$name}")->assertStatus(401);
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable $closure
     * @param string|null $title
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_files')]
    public function test_upload_failure_validation(string $name, string $path, callable $closure, ?string $title)
    {
        $this->fakeStorages();
        [$user, $model] = $closure($this);
        $mime = mime_content_type(storage_path('app/fake/' . $path));
        $entity = $model->getMorphClass();

        // Unsupported file
        $this
            ->post(
                "/api/file/upload/{$entity}/{$model->id}/{$name}",
                ['file' => new UploadedFile(storage_path('app/fake/01.foo'), '01.foo', null, null, true), 'title' => $title]
            )
            ->assertStatus(422)
            ->assertJson(['message' => true, 'errors' => ['file' => true]]);

        // Incorrect name
        $this
            ->post(
                "/api/file/upload/{$entity}/{$model->id}/foo",
                ['file' => new UploadedFile(storage_path('app/fake/' . $path), basename($path), $mime, null, true), 'title' => $title]
            )
            ->assertStatus(422)
            ->assertJson(['message' => true, 'errors' => ['file.visibility' => true]]);

        // Missed file
        $this
            ->post("/api/file/upload/{$entity}/{$model->id}/{$name}")
            ->assertStatus(422)
            ->assertJson(['message' => true, 'errors' => ['error' => true]]);


        // Entity id not exists
        $this
            ->post(
                "/api/file/upload/{$entity}/".($model->id + 100)."/{$name}",
                ['file' => new UploadedFile(storage_path('app/fake/' . $path), basename($path), $mime, null, true), 'title' => $title]
            )
            ->assertStatus(422)
            ->assertJson(['message' => true, 'errors' => ['file.entity_id' => true]]);

        // Incorrect entity
        $this
            ->post(
                "/api/file/upload/foo/{$model->id}/{$name}",
                ['file' => new UploadedFile(storage_path('app/fake/' . $path), basename($path), $mime, null, true), 'title' => $title]
            )
            ->assertStatus(422)
            ->assertJson(['message' => true, 'errors' => ['file.entity' => true]]);
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable $closure
     * @param string|null $title
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_files')]
    public function test_upload_failure_acl(string $name, string $path, callable $closure, ?string $title)
    {
        $this->fakeStorages();
        $mime = mime_content_type(storage_path('app/fake/' . $path));
        [$user, $model] = $closure($this);
        $entity = $model->getMorphClass();

        if (count($user->role_details['abilities']) > 1) {
            config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
            $this
                ->post(
                    "/api/file/upload/{$entity}/{$model->id}/{$name}",
                    ['file' => new UploadedFile(storage_path('app/fake/' . $path), basename($path), $mime, null, true), 'title' => $title]
                )
                ->assertStatus(403);
        }

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this
            ->post(
                "/api/file/upload/{$entity}/{$model->id}/{$name}",
                ['file' => new UploadedFile(storage_path('app/fake/' . $path), basename($path), $mime, null, true), 'title' => $title]
            )
            ->assertStatus(403);
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable $closure
     * @param string|null $title
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_files')]
    public function test_delete_success(string $name, string $path, callable $closure, ?string $title)
    {
        $this->fakeStorages();
        [$user, $model] = $closure($this);
        $fileVirtual = $this->createFile($model, $name, [$path]);

        $this
            ->post("/api/file/delete/{$fileVirtual->id}")
            ->assertStatus(200)
            ->assertExactJson(['file_virtual' => ['id' => $fileVirtual->id]]);

        $this->assertNull($fileVirtual->fresh());
    }

    /**
     * @return void
     */
    public function test_delete_failure_not_auth()
    {
        $this->post('/api/file/delete/0')->assertStatus(401);
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable $closure
     * @param string|null $title
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_files')]
    public function test_delete_failure_not_found(string $name, string $path, callable $closure, ?string $title)
    {
        $this->fakeStorages();
        [$user, $model] = $closure($this);

        $this
            ->post('/api/file/delete/0')
            ->assertStatus(404)
            ->assertJson(['message' => true, 'errors' => []]);
    }

    /**
     * @param string $name
     * @param string $path
     * @param callable $closure
     * @param string|null $title
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_files')]
    public function test_delete_failure_acl(string $name, string $path, callable $closure, ?string $title)
    {
        $this->fakeStorages();
        [$user, $model] = $closure($this);
        $fileVirtual = $this->createFile($model, $name, [$path]);

        if (count($user->role_details['abilities']) > 1) {
            config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
            $this->post("/api/file/delete/{$fileVirtual->id}")->assertStatus(403);
        }

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->post("/api/file/delete/{$fileVirtual->id}")->assertStatus(403);


        $this->assertNotNull($fileVirtual->fresh());
    }
}
