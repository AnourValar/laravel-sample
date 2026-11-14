<?php

namespace App\Services;

class SettingService
{
    /**
     * @var string
     */
    private const CACHE_KEY = 'App\\Services\\SettingService';

    /**
     * Возвращает настройки
     *
     * @param string|null $name
     * @return array
     */
    public function get(?string $name = null): array
    {
        $settings = \Cache::memo()->rememberForever(self::CACHE_KEY, fn () => \App\Setting::get()->pluck('value', 'name')->toArray());

        if ($name) {
            return $settings[$name];
        }
        return $settings;
    }

    /**
     * Сохраняет настройки
     * На уровне вызова метода должна быть обеспечена атомарность и race-condition resolve
     *
     * @param mixed $data
     * @param mixed $validationPrefix
     * @return void
     */
    public function put($data, $validationPrefix = null): void
    {
        $settings = \App\Setting::get();

        foreach ((array) $data as $name => $value) {
            $setting = $settings->firstWhere('name', $name) ?? new \App\Setting();

            try {
                $setting->forceFill(['name' => $name, 'value' => $value])->validate($validationPrefix)->save();
            } catch (\AnourValar\EloquentValidation\Exceptions\ValidationException $e) {
                throw $e->replaceKey('value', $name);
            }
        }

        $this->flush();
    }

    /**
     * Очищает кэш настроек
     *
     * @return void
     */
    private function flush(): void
    {
        \Cache::memo()->delete(self::CACHE_KEY);
    }
}
