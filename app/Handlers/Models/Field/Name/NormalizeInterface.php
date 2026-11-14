<?php

namespace App\Handlers\Models\Field\Name;

interface NormalizeInterface
{
    /**
     * Нормалиация данных
     *
     * @param \App\Field $field
     * @return array|null
     */
    public function normalizeData(\App\Field $field): ?array;
}
