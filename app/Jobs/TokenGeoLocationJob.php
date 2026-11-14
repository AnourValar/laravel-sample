<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TokenGeoLocationJob implements /*ShouldBeUnique,*/ ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $personalAccessTokenId, public string $ipAddress)
    {
        //
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {
        return [
            new \AnourValar\LaravelAtom\Jobs\Middleware\ValidationExceptionHandler(),
        ];
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    /*public function uniqueId()
    {
        return (string) $this->personalAccessTokenId;
    }*/

    // /**
    //  * The number of times the job may be attempted.
    //  *
    //  * @var int
    //  */
    // public $tries = 1;

    /**
     * Execute the job.
     *
     * @param \App\Adapters\GeoLocation\GeoLocationInterface $adapter
     * @return void
     */
    public function handle(\App\Adapters\GeoLocation\GeoLocationInterface $adapter): void
    {
        if ($geoLocation = $adapter->fetch($this->ipAddress)) {
            \DB::table('personal_access_tokens')->where('id', '=', $this->personalAccessTokenId)->update(['geo_location' => $geoLocation]);
        }
    }
}
