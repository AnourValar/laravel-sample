<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Diagnostic
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->replace(['fresh' => true]);
        config(['health.result_stores' => \Spatie\Health\ResultStores\InMemoryHealthResultStore::class]);
        config(['health.notifications.enabled' => false]);

        \Spatie\Health\Facades\Health::checks([
            // spatie/laravel-health
            //\Spatie\Health\Checks\Checks\ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2), // ScheduleCheckHeartbeatCommand + ASP
            //\Spatie\Health\Checks\Checks\QueueCheck::new()->failWhenHealthJobTakesLongerThanMinutes(2), // DispatchQueueCheckJobsCommand + ASP
            \Spatie\Health\Checks\Checks\CacheCheck::new(),
            \Spatie\Health\Checks\Checks\RedisCheck::new(),
            \Spatie\Health\Checks\Checks\DatabaseCheck::new(),
            \Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck::new()->failWhenMoreConnectionsThan(70),
            \Spatie\Health\Checks\Checks\UsedDiskSpaceCheck::new()->warnWhenUsedSpaceIsAbovePercentage(90),
            \Spatie\Health\Checks\Checks\PingCheck::new()->name(url(''))->label('Ping')->url(url('')),
            \Spatie\Health\Checks\Checks\HorizonCheck::new(),
            \Spatie\Health\Checks\Checks\OptimizedAppCheck::new(),
            \Spatie\Health\Checks\Checks\DebugModeCheck::new(),
            \Spatie\Health\Checks\Checks\EnvironmentCheck::new(),
            \Spatie\Health\Checks\Checks\DatabaseSizeCheck::new()->failWhenSizeAboveGb(5.0),
            \Spatie\Health\Checks\Checks\RedisMemoryUsageCheck::new()->failWhenAboveMb(1000),

            // anourvalar/laravel-health
            \AnourValar\LaravelHealth\QueueFailedCheck::new(),
            //\AnourValar\LaravelHealth\PusherCheck::new(),
            \AnourValar\LaravelHealth\SSLCertCheck::new()->url(url(''))->warnWhenExpiringDay(10)->failWhenExpiringDay(2),
            \AnourValar\LaravelHealth\CpuLoadCheck::new()->failWhenLoadIsHigher(2.5, 2.0, 1.5),
            \AnourValar\LaravelHealth\QueueSizeCheck::new()->add(['connection' => null, 'name' => null, 'max_size' => 20]),
            \AnourValar\LaravelHealth\XdebugCheck::new(),
            \AnourValar\LaravelHealth\OpcacheCheck::new(),
            \AnourValar\LaravelHealth\FilesystemCheck::new()->withDirectory(true)->disks($this->getDisks()),
            \AnourValar\LaravelHealth\GzipCheck::new()->shouldBeGzipped($this->getGzipped())->shouldNotBeGzipped($this->getNotGzipped()),
            \AnourValar\LaravelHealth\CacheHeadersCheck::new()->shouldBeCached($this->getCached())->shouldNotBeCached($this->getNotCached()),
            \AnourValar\LaravelHealth\ReverseProxySecurityCheck::new()->url(route('health-ping')), // HealthPingController
            \AnourValar\LaravelHealth\Http2HttpsCheck::new()->shouldBeRedirected($this->getRedirects()),
            \AnourValar\LaravelHealth\Www2NoneCheck::new()->shouldBeRedirected($this->getRedirects()),
            \AnourValar\LaravelHealth\MailerCheck::new(),
            //\AnourValar\LaravelHealth\SentryCheck::new(),
            \AnourValar\LaravelHealth\DirectoryPermissionsCheck::new()->writable(storage_path('logs')), // local disks are checking with FilesystemCheck
            \AnourValar\LaravelHealth\CorsCheck::new()->allowed($this->getCors())->disallowed(['http://foo.bar', $this->getSubdomain('f', '')])->url(route('health-ping')),
            \AnourValar\LaravelHealth\RootCheck::new()->shouldBeRoot(false),
            \AnourValar\LaravelHealth\FastCGICheck::new()->url(route('health-ping')), // HealthPingController
            \AnourValar\LaravelHealth\RedisConfigCheck::new(),
            \AnourValar\LaravelHealth\DBConfigCheck::new()->params(['transaction_isolation' => 'read committed', 'TimeZone' => 'GMT']),
            \AnourValar\LaravelHealth\HttpV2Check::new()->urls('/'),
            \AnourValar\LaravelHealth\HstsCheck::new()->urls('/'),
            //\AnourValar\LaravelHealth\OctaneCheck::new(),
        ]);

        return $next($request);
    }

    /**
     * @return array
     */
    private function getDisks(): array
    {
        return array_merge(
            array_fill_keys(config('eloquent_file.file_physical.visibility.public.disks'), true),
            array_fill_keys(config('eloquent_file.file_physical.visibility.private.disks'), false),
        );
    }

    /**
     * @return array
     */
    private function getCors(): array
    {
        $hosts = [];

        $hosts[] = $this->getSubdomain('', '');
        $hosts[] = $this->getSubdomain('', '') . ':123';
        $hosts[] = $this->getSubdomain('foo');
        $hosts[] = $this->getSubdomain('foo') . ':123';

        return $hosts;
    }

    /**
     * @return array
     */
    private function getGzipped(): array
    {
        return ['/', '/static-admin/telegram.svg']; // + getSubdomain()?
    }

    /**
     * @return array
     */
    private function getNotGzipped(): array
    {
        return ['/static-admin/favicon.png']; // + getSubdomain()?
    }

    /**
     * @return array
     */
    private function getCached(): array
    {
        return ['/static-admin/favicon.ico']; // + getSubdomain()?
    }

    /**
     * @return array
     */
    private function getNotCached(): array
    {
        return ['/']; // + getSubdomain?
    }

    /**
     * @return array
     */
    private function getRedirects(): array
    {
        return ['/']; // + getSubdomain()?
    }

    /**
     * @param string $sub
     * @param string $delimiter
     * @return string
     */
    private function getSubdomain(string $sub, string $delimiter = '.'): string
    {
        return preg_replace('#^(https?://)#i', '$1' . $sub . $delimiter, url('/'));
    }
}
