<?php

namespace App\Adapters\GeoLocation;

interface GeoLocationInterface
{
    /**
     * Вычисляет по IP (⌐■_■)
     *
     * @param string $ip
     * @return string|null
     * @throws \App\Exceptions\ExternalException
     */
    public function fetch(string $ip): ?string;
}
