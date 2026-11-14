<?php

namespace App\Handlers\Entities\Analytics;

use AnourValar\LaravelAtom\AnalyticsInterface;

//use AnourValar\Office\Drivers\GridInterface;
//use AnourValar\Office\Facades\ExportGridInterface;
//use AnourValar\Office\Facades\ExportService;

class UserTotalsAnalytics extends \AnourValar\LaravelAtom\Repository implements AnalyticsInterface
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\LaravelAtom\AnalyticsInterface::validate()
     */
    public function validate(?\Illuminate\Foundation\Auth\User $user, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('handlers/entities/analytics/user_totals.attributes'));

        $validator->addRules([
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'in:20,50,100'],
            'sort_column' => ['required', 'string', 'in:id,created_at,last_login_at,deleted_at'],
            'sort_direction' => ['required', 'string', 'in:ASC,DESC'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\LaravelAtom\AnalyticsInterface::authorize()
     */
    public function authorize(?\Illuminate\Foundation\Auth\User $user, array $data): void
    {
        if (! $user?->can('cabinet.user.read')) {
            throw new \Illuminate\Auth\Access\AuthorizationException();
        }
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\LaravelAtom\AnalyticsInterface::cacheTag()
     */
    public function cacheTag(array $data): ?string
    {
        return null; // "user:{$data['user_id']}"
    }

    /**
     * Сводная аналитика по пользователям
     *
     * {@inheritDoc}
     * @see \AnourValar\LaravelAtom\AnalyticsInterface::getData()
     */
    public function getData(array $data): iterable
    {
        ['limit' => $limit, 'offset' => $offset] = $this->calculateSimplePaginate($data['per_page'], $data['page']);

        $sortColumn = $data['sort_column'];
        $sortDirection = $data['sort_direction'];

        $result = \DB::select(
            <<<HERE
                SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.middle_name,
                    u.created_at,
                    (
                        SELECT MAX(j.created_at)
                        FROM journals j
                        WHERE j.user_id = u.id
                          AND j.event IN ('user_session_obtain', 'user_token_obtain')
                          AND j.success = TRUE
                    ) AS last_login_at,
                    u.deleted_at
                FROM users u
                ORDER BY {$sortColumn} {$sortDirection}, u.id ASC
                LIMIT {$limit} OFFSET {$offset}
            HERE,
            [

            ]
        );

        return $this->simplePaginate($this->toArray($result), $data['per_page'], $data['page']);
    }
}
