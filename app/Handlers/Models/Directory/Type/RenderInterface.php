<?php

namespace App\Handlers\Models\Directory\Type;

interface RenderInterface
{
    /**
     * Рендеринг значения справочника (показ)
     *
     * @param \App\Directory $directory
     * @return string
     */
    public function renderShow(\App\Directory $directory): string;

    /**
     * Рендеринг значения справочника (редактирование)
     *
     * @param \App\Directory $directory
     * @param string $name
     * @return string
     */
    public function renderEdit(\App\Directory $directory, string $name): string;
}
