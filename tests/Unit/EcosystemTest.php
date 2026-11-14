<?php

namespace Tests\Unit;

use Tests\TestCase;

class EcosystemTest extends TestCase
{
    /**
     * @var array
     */
    private $preloadExclude = [
        '/bootstrap/cache/config.php',
        '/bootstrap/cache/routes-v7.php',
        '/bootstrap/cache/events.php',
    ];

    /**
     * @return void
     */
    public function test_config()
    {
        $this->assertEquals('testing', config('app.env'));
        $this->assertEquals('array', config('cache.default'));
        $this->assertEquals('array', config('mail.default'));
        $this->assertEquals('sync', config('queue.default'));
        $this->assertEquals('array', config('session.driver'));
    }

    /**
     * @return void
     */
    public function test_preload()
    {
        $file = file_get_contents(base_path('preload.php'));

        $filesCompile = explode('/* -- START -- */', $file)[1];
        $filesCompile = explode('/* -- FINISH -- */', $filesCompile)[0];
        eval($filesCompile);

        foreach ($filesCompile as $file) {
            $file = str_replace(__DIR__, '', $file);

            foreach ($this->preloadExclude as $exclude) {
                if (stripos($file, $exclude) === 0) {
                    continue 2;
                }
            }

            $this->assertTrue($this->checkFile($file), "File: {$file}");
        }
    }

    /**
     * @return void
     */
    public function test_locale()
    {
        $this->assertEquals('ТЕСТ', mb_strtoupper('тест'));
        $this->assertEquals('Hello', ucfirst('hello'));
        $this->assertEquals('привет, мир!', mb_strtolower('ПРИВЕТ, МИР!'));

        $this->assertEquals('Тест', htmlspecialchars('Тест'));
        $this->assertEquals('Test', htmlspecialchars('Test'));

        \App::setLocale('ru');
        $this->assertEquals('1 мин.', \Carbon\Carbon::now()->shortAbsoluteDiffForHumans(\Carbon\Carbon::now()->addMinute()));
        $this->assertSame('1 день после', now()->diffForHumans(now()->subDays(1)->subHours(1)));
        $this->assertSame('1 секунду назад', now()->addSeconds(-1)->diffForHumans());
        $this->assertEquals('ru', \App::getLocale());
        $this->assertEquals('ru', config('app.locale'));
        $this->assertEquals('Вы должны принять :attribute.', trans('validation.accepted'));

        \App::setLocale('en');
        $this->assertEquals('1m', now()->shortAbsoluteDiffForHumans(now()->addMinute()));
        $this->assertSame('1 day after', now()->diffForHumans(now()->subDays(1)->subHours(1)));
        $this->assertSame('1 second ago', now()->addSeconds(-1)->diffForHumans());
        $this->assertEquals('en', \App::getLocale());
        $this->assertEquals('en', config('app.locale'));
        $this->assertEquals('The :attribute field must be accepted.', trans('validation.accepted'));
    }

    /**
     * @return void
     */
    public function test_config_field()
    {
        $config = config('models.field.name');

        $this->checkConfigfieldTitle($config);

        foreach ($config as $name => $details) {
            $screen = array_first(array_filter($details['ui'], fn ($item) => stripos($item, 'screen:') !== false));
            $screen = explode(':', $screen, 2)[1];

            $this->assertStringContainsString("{$screen}_", $name);
            $this->assertStringContainsString("models/field.name.{$screen}_", $details['title']);
        }
    }

    /**
     * @param array $item
     * @param array $path
     * @return void
     */
    private function checkConfigfieldTitle(array $item, array $path = []): void
    {
        foreach ($item as $key => $value) {
            if (is_string($value)) {
                $this->assertStringNotContainsString('models/', trans($value), implode('.', [...$path, $key]));
            }

            if (is_array($value)) {
                $this->checkConfigfieldTitle($value, [...$path, $key]);
            }
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    private function checkFile(string $path): bool
    {
        $basename = basename($path);
        $dirname = dirname($path);

        $prev = base_path('') . '/';
        foreach (explode('/', $dirname) as $dirname) {
            if (! mb_strlen($dirname)) {
                continue;
            }

            if (! in_array($dirname, scandir($prev))) {
                return false;
            }

            $prev .= $dirname . '/';
        }

        if (! in_array($basename, scandir($prev))) {
            return false;
        }

        return true;
    }
}
