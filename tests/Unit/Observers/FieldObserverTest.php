<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;

class FieldObserverTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_saving_normalize_data()
    {
        config(['models.field.name.test_integer' => [
            'title' => 'Test Integer',
            'bind' => \App\Handlers\Models\Field\Name\IntegerName::class,
            'ui' => ['widget:number'],
            'tags' => [],
            'file_virtuals' => [],
            'options' => ['min' => 1, 'max' => 100],
        ]]);

        $field = \App\Field::factory()->create(['name' => 'test_integer', 'data' => ['value' => '7']]);
        $this->assertSame(7, $field->refresh()->data['value']);
    }

    /**
     * @return void
     */
    public function test_deleting_file_virtuals()
    {
        $field = \App\Field::factory()->create();
        $fileVirtual = $this->createFile($field, 'attachment');

        $this->assertNotNull(\App\FileVirtual::find($fileVirtual->id));
        $field->delete();
        $this->assertNull(\App\FileVirtual::find($fileVirtual->id));
    }
}
