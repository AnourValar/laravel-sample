<?php

namespace App\Handlers\Models\Directory\Type;

class UserTagType implements RenderInterface, TypeInterface
{
    use \AnourValar\LaravelAtom\Traits\PostgresTrait;

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Directory\Type\TypeInterface::validate()
     */
    public function validate(\App\Directory $directory, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/directory.type_handler.user_tag.attributes'));

        $validator->addRules([
            'data' => ['array_keys_auto'],
                'data.title' => ['required', 'string', 'min:2', 'max:40'],
                'data.color' => ['required', 'string', 'bail', 'hex_color'],
                'data.description' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Directory\Type\TypeInterface::search()
     */
    public function search(\App\Directory $directory): array
    {
        return [$directory->data['title']];
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Directory\Type\RenderInterface::renderShow()
     */
    public function renderShow(\App\Directory $directory): string
    {
        return view('handlers.models.directory.type.user_tag_show', compact('directory'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Directory\Type\RenderInterface::renderEdit()
     */
    public function renderEdit(\App\Directory $directory, string $name): string
    {
        return view('handlers.models.directory.type.user_tag_edit', compact('directory', 'name'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Directory\Type\TypeInterface::onDeleted()
     */
    public function onDeleted(\App\Directory $directory): void
    {
        \App\User::query()
            ->withTrashed()
            ->whereJsonContains('tag_ids', $directory->id)
            ->update(['tag_ids' => $this->jsonbDiff('tag_ids', $directory->id)]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Directory\Type\TypeInterface::generateFake()
     */
    public function generateFake(string $type, \Faker\Generator $faker): array
    {
        do {
            $title = mt_rand(0, 2) ? $faker->city() : $faker->country();
            $title = explode(',', $title)[0];
        } while (mb_strlen($title) > 40);

        return array_filter([
            'title' => $title,
            'color' => $faker->hexColor(),
            'description' => mt_rand(0, 1) ? $faker->realTextBetween(10, 80) : null,
        ]);
    }
}
