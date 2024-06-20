<?php


namespace CrucialDigital\Metamorph;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\EmbedsMany;
use MongoDB\Laravel\Relations\EmbedsOne;

class ResourceQueryLoader
{
    protected Builder|Model|null $builder;

    public function __construct(Builder|Model|null $builder)
    {
        $this->builder = $builder;
    }

    public function load($columns = ['*']): LengthAwarePaginator|array|Expression|Collection
    {
        if (!($this->builder instanceof Builder)) {
            return [];
        }
        $per_page = (int)request()->query('per_page', 15);
        $order_by = request()->query('order_by', 'created_at');
        $order_direction = request()->query('order_direction', 'ASC');
        $paginate = (bool)request()->query('paginate', true);
        $randomize = (bool)request()->query('randomize', false);
        $with_trash = (bool)request()->query('with_trash', false);
        $only_trash = (bool)request()->query('only_trash', false);
        $with = static::makeRelations($this->builder);

        $search = request()->input('search', []);
        $this->search($search);
        $filters = request()->input('filters', []);
        if ($filters) {
            $this->filter($filters);
        }
        $columns = request()->input('columns', $columns);

        $this->builder = $this->builder->select($columns);

        foreach (explode('|', $order_by) as $k => $str) {
            $directions = explode('|', $order_direction);
            $direction = $directions[$k] ?? $directions[0];
            $this->builder = $this->builder->orderBy($str, $direction);
        }

        if ($only_trash) {
            $this->builder = $this->builder->onlyTrashed();
        }

        if ($with_trash) {
            $this->builder = $this->builder->withTrashed();
        }

        if ($randomize) {
            $data = $this->builder->raw(function ($collection) use ($per_page) {
                return $collection->aggregate([
                    [
                        '$sample' => [
                            'size' => $per_page
                        ]
                    ],
                ]);
            });
            if ($with != null) $data = $data->load($with);
            return $data;
        } else {
            if ($with != null) $this->builder = $this->builder->with($with);
        }

        return $paginate
            ? $this->builder->paginate($per_page, $columns)
            : $this->builder->get($columns);
    }


    /**
     * @param mixed $search
     * @return void
     */
    private function search(mixed $search): void
    {
        $queries = (!is_array($search)) ? (json_decode($search, true) ?? []) : $search;
        $term = request()->query('term');
        if (isset($term)) {
            $columns = $this->builder->getModel()::class::search() ?? [];
            foreach ($columns as $column) {
                $queries[$column] = $term;
            }
        }
        if ($queries != null && count($queries) > 0) {
            $this->builder->where(function (Builder $builder) use ($queries) {
                $i = 0;
                foreach ($queries as $k => $query) {
                    if ($i == 0) {
                        if ($k != '_id') {
                            $builder->where($k, 'LIKE', '%' . $query . '%');
                        } else {
                            if (Str::contains($k, '.')) {
                                $builder->whereHas($k, $query);
                            } else {
                                $builder->where($k, $query);
                            }
                        }
                    } else {
                        if ($k != '_id') {
                            $builder->orWhere($k, 'LIKE', '%' . $query . '%');
                        } else {
                            if (Str::contains($k, '.')) {
                                $builder->orWhereHas($k, $query);
                            } else {
                                $builder->orWhere($k, $query);
                            }
                        }
                    }
                    $i++;
                }
            });
        }
    }

    /**
     * @param $queries
     * @return void
     */
    private function filter($queries): void
    {
        $filters = (is_string($queries)) ? json_decode($queries, false) : $queries;


        if ($filters !== null && count($filters) > 0) {
            $global_filters = collect($filters)->where(function ($filter) {
                return !isset($filter['group']) || $filter['group'] == null || $filter['group'] == '';
            });
            foreach ($global_filters as $global_filter) {
                $this->bindFieldFilter($global_filter);
            }

            $grouped_filters = collect($filters)->where(function ($filter) {
                return isset($filter['group']) && $filter['group'] != null && $filter['group'] != '';
            })->groupBy('group');
            foreach ($grouped_filters as $group => $group_filters) {
                $groupCoordinator = explode('_', $group)[0];

                $groupCoordinator = in_array(Str::lower($groupCoordinator), ['and', 'or'])
                    ? Str::lower($groupCoordinator)
                    : 'and';

                $this->builder->where(function (Builder $builder) use ($group_filters) {
                    foreach ($group_filters as $group_filter) {
                        $this->bindFieldFilter($group_filter, $builder);
                    }
                }, boolean: $groupCoordinator);
            }
        }
    }


    /**
     * @param $builder
     * @return array|null
     */
    public static function makeRelations(&$builder): array|null
    {
        $query_relations = request()->query('relations');
        $query_relations = isset($query_relations) ? explode(',', $query_relations) : [];
        $input_relations = is_array(request()->input('relations'))
            ? request()->input('relations')
            : [];

        $with = [...$query_relations, ...$input_relations];
        return count($with) > 0 ? collect($with)->filter(function ($relation) use (&$builder) {
            return method_exists(get_class($builder->getModel()), $relation);
        })->toArray() : null;
    }

    /**
     * @param mixed $value
     * @return Carbon[]
     */
    protected function getDateArrayValue(mixed $value): array
    {
        if (is_array($value)) {
            if (count($value) >= 2) {
                $value = [new Carbon($value[0]), new  Carbon($value[1])];
            } else {
                $value = [new Carbon($value[0] ?? null), new  Carbon($value[0] ?? null)];
            }
        } else {
            $value = [new Carbon($value), new  Carbon($value)];
        }
        return $value;
    }

    /**
     * @param $field
     * @param $operator
     * @param $value
     * @param string $coordinator
     * @param Builder|null $builder
     * @return void
     */
    protected function bindQuery($field, $operator, $value, string $coordinator = 'and', Builder $builder = null): void
    {
        if ($builder == null) {
            //In case of relation sub-query, don't use the global builder
            $builder = $this->builder;
        }
        switch (Str::upper($operator)) {
            case 'LIKE':
                $builder->where($field, 'LIKE', '%' . $value . '%', Str::lower($coordinator));
                break;
            case 'IN':
                $value = is_array($value) ? $value : [$value];
                $builder->whereIn($field, $value, Str::lower($coordinator));
                break;
            case 'NOTIN':
                $value = is_array($value) ? $value : [$value];
                $builder->whereNotIn($field, $value, Str::lower($coordinator));
                break;
            case 'BETWEEN':
                $value = is_array($value) ? $value : [$value, $value];
                $builder->whereBetween($field, $value, Str::lower($coordinator));
                break;
            case 'NOTBETWEEN':
                $value = is_array($value) ? $value : [$value, $value];
                $builder->whereNotBetween($field, $value, Str::lower($coordinator));
                break;
            case 'DATE':
                $builder->whereDate($field, '=', $value, Str::lower($coordinator));
                break;
            case 'DATEBEFORE':
                $builder->whereDate($field, '<', $value, Str::lower($coordinator));
                break;
            case 'DATEAFTER':
                $builder->whereDate($field, '>', $value, Str::lower($coordinator));
                break;
            case 'DATEBEFOREQ':
                $builder->whereDate($field, '<=', $value, Str::lower($coordinator));
                break;
            case 'DATEAFTEREQ':
                $builder->whereDate($field, '>=', $value, Str::lower($coordinator));
                break;
            case 'DATENOT':
                $builder->whereDate($field, '!=', $value, Str::lower($coordinator));
                break;
            case 'DATEBETWEEN':
                $value = $this->getDateArrayValue($value);
                $builder->whereBetween($field, $value, Str::lower($coordinator));
                break;
            case 'DATENOTBETWEEN':
                $value = $this->getDateArrayValue($value);
                $builder->whereNotBetween($field, $value, Str::lower($coordinator));
                break;
            default:
                $builder->where($field, $operator, $value, Str::lower($coordinator));
        }
    }

    /**
     * @param $input
     * @param $builder
     * @return void
     */
    protected function bindFieldFilter($input, $builder = null): void
    {
        if ($builder == null) {
            //In case of relation sub-query, don't use the global builder
            $builder = $this->builder;
        }
        $field = $input['field'] ?? null;
        $operator = $input['operator'] ?? '=';
        $value = $input['value'] ?? null;
        $coordinator = (isset($input['coordinator'])
            && in_array(Str::lower($input['coordinator']), ['and', 'or']))
            ? $input['coordinator']
            : 'and';

        if ($field != null) {
            if (str_contains($field, '.')) {
                $parts = explode('.', $field);
                $last = array_pop($parts);
                $relations = implode('.', $parts);
                $first = array_shift($parts);
                $relationClass = $this->relationExists($first, $builder);

                if ($relationClass) {
                    if ($relationClass instanceof EmbedsOne || $relationClass instanceof EmbedsMany) {
                        $this->bindQuery($relations . '.' . $last, $operator, $value, $coordinator, $builder);
                    } else {
                        $builder->whereHas($relations, function (Builder $builder) use ($last, $operator, $value, $coordinator) {
                            $this->bindQuery($last, $operator, $value, $coordinator, $builder);
                        });
                    }
                }

            } else {
                $this->bindQuery($field, $operator, $value, $coordinator, $builder);
            }
        }
    }

    private function relationExists(string $relation, Builder $builder): bool|Relation
    {
        try {
            return $builder->getRelation($relation);
        } catch (\Exception $e) {
            return false;
        }
    }
}
