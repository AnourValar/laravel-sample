<?php

namespace App\Adapters\GeoLocation;

class IpWhoIsAdapter implements GeoLocationInterface
{
    /**
     * @var \AnourValar\HttpClient\Http
     */
    protected $http;

    /**
     * Зависимости
     */
    public function __construct()
    {
        $this->http = \App::make(\AnourValar\HttpClient\Http::class)
            ->remember(function (\AnourValar\HttpClient\Http $http) {
                $http->timeouts(5000, 10000)->extendInfo()->asJsonClient();
            });
    }

    /**
     * @see https://ipwhois.io/documentation [60 rpm is free]
     *
     * {@inheritDoc}
     * @see \App\Adapters\GeoLocation\GeoLocationInterface::fetch()
     */
    public function fetch(string $ip): ?string
    {
        $response = $this
            ->http
            ->method('GET')
            ->baseUrl('https://ipwho.is/')
            ->exec($ip);

        if ($response->success('success')) {
            return implode(', ', array_unique([$response['country'], $response['region'], $response['city']]));
        }

        if (($response['message'] ?? '') == 'Invalid IP address') {
            return null;
        }

        throw new \App\Exceptions\ExternalException('ipwhois.get_location', $response->dump(true));
    }
}
