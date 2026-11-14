<?php

namespace Tests\Feature;

use Tests\TestCase;

class FileTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * {@inheritDoc}
     * @see \Illuminate\Foundation\Testing\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['eloquent_file.file_physical.visibility.private.proxy_route_method' => \AnourValar\EloquentFile\Handlers\Models\FilePhysical\Visibility\PrivateVisibility::METHOD_URL_SIGNED]);
    }

    /**
     * @return void
     */
    public function test_download_success()
    {
        $model = \App\Field::factory()->create();
        $fileVirtual = $this->createFile($model, 'attachment', fake: false);


        // Iteration 1
        $file = $this
            ->get(
                \URL::signedRoute('file.download', ['file_virtual' => $fileVirtual->id, 'filename' => $fileVirtual->filename], now()->addMinute()),
            )
            ->assertStatus(200)
            ->streamedContent();

        $this->assertSame(file_get_contents(storage_path('app/fake/pdf/01.pdf')), $file);


        // Iteration 2
        $file = $this
            ->get($fileVirtual->load('filePhysical')->url)
            ->assertStatus(200)
            ->streamedContent();

        $this->assertSame(file_get_contents(storage_path('app/fake/pdf/01.pdf')), $file);
    }

    /**
     * @return void
     */
    public function test_download_failure_acl()
    {
        \Date::setTestNow('2025-09-29 15:00:00');
        $model = \App\Field::factory()->create();
        $fileVirtual = $this->createFile($model, 'attachment', fake: false);
        $url = $fileVirtual->load('filePhysical')->url;

        // Iteration 1
        $this->get($url)->assertStatus(200);
        $this->get($url.'foo')->assertStatus(403);

        // Iteration 2
        \Date::setTestNow('2025-09-30 15:00:00');
        $this->get($url)->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_download_failure_not_found()
    {
        $model = \App\Field::factory()->create();
        $fileVirtual = $this->createFile($model, 'attachment', fake: false);
        $fileVirtual->delete();

        $this->get($fileVirtual->load('filePhysical')->url)->assertStatus(404);
    }
}
