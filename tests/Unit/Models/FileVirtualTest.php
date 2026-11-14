<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class FileVirtualTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_light()
    {
        \Date::setTestNow('2024-08-13 11:00:00');
        $fileVirtual = $this->createFile(\App\Field::factory()->create(), 'attachment');
        $fileVirtual->load('filePhysical');

        // light
        $this->assertSame(
            [
                'id' => $fileVirtual->id,
                'name' => 'attachment',
                'filename' => $fileVirtual->filename,
                'title' => $fileVirtual->title,
                'created_at' => '2024-08-13T11:00:00.000000Z',
                'mime_type' => $fileVirtual->mime_type,
                'size' => $fileVirtual->size,
                'url' => $fileVirtual->url,
                'url_generate' => $fileVirtual->url_generate,
            ],
            \App\FileVirtual::light()->find($fileVirtual->id)->toArray()
        );
    }
}
