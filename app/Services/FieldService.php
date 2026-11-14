<?php

namespace App\Services;

class FieldService
{
    /**
     * Возвращает поля пользователя (схема со значениями)
     *
     * @param \App\User $user
     * @param bool $asArray
     * @return array
     */
    public function fields(\App\User $user, bool $asArray = true): array
    {
        $result = \Cache::memo()->remember($this->cacheKey($user->id), 86400, function () use ($user) {
            $result = [];
            $fields = \App\Field::light()->where('user_id', '=', $user->id)->get()->pluck(null, 'name');
            $tags = $this->getUserTags($fields);

            foreach ($this->getUserFields($tags) as $name => $details) {
                $result[] = [
                    'name' => $name,
                    'title' => $details['title'],
                    'ui' => $details['ui'],
                    'options' => $details['options'] ? \ConfigHelper::publish($details['options'], ['title'], ['title']) : $details['options'],

                    'field' => $fields[$name] ?? null,
                ];
            }

            return $result;
        });

        foreach ($result as &$item) {
            $item['title'] = trans($item['title']);
            if ($asArray) {
                $item['field'] = isset($item['field']) ? $item['field']->toArray() : $item['field'];
            }
        }
        unset($item);

        return $result;
    }

    /**
     * Возвращает заполненные поля пользователя в RAW виде
     *
     * @param \App\User $user
     * @return array
     */
    public function fieldsRaw(\App\User $user): array
    {
        $result = [];
        foreach ($this->fields($user, false) as $item) {
            if (! isset($item['field'])) {
                continue;
            }

            $result[$item['name']] = trim($item['field']->getNameHandler()->renderShow($item['field'], true));
        }

        return $result;
    }

    /**
     * Очистка кэша
     *
     * @param int $userId
     * @return void
     */
    public function flushCache(int $userId): void
    {
        \Cache::memo()->forget($this->cacheKey($userId));
    }

    /**
     * Разворачивает конфиг полей (с учётом wildcard)
     *
     * @param string $path
     * @return void
     */
    public static function expandConfig(string $path = 'models.field.name'): void
    {
        $expanded = [];

        foreach ((array) config($path) as $key => $details) {
            if (str_ends_with($key, '*') && ! empty($details['wildcard'])) {
                $prefix = substr($key, 0, -1);
                foreach ($details['wildcard'] as $suffix) {
                    $expanded[$prefix . $suffix] = $details;
                }
            } else {
                $expanded[$key] = $details;
            }
        }

        config([$path => $expanded]);
    }

    /**
     * @param \Illuminate\Support\Collection $fields
     * @return array
     */
    private function getUserTags(\Illuminate\Support\Collection $fields): array
    {
        $tags = [];

        foreach ($fields as $item) {
            $tags = array_merge($tags, $item->getNameHandler()->tags($item));
        }

        return $tags;
    }

    /**
     * @param array $tags
     * @return array
     */
    private function getUserFields(array $tags): array
    {
        $result = [];

        foreach (config('models.field.name') as $key => $value) {
            if (array_intersect($value['tags'], $tags) || ! $value['tags']) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param int $userId
     * @return string
     */
    private function cacheKey(int $userId): string
    {
        return implode(' / ', [__METHOD__, $userId]);
    }
}
