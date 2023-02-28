<?php


namespace CrucialDigital\Metamorph;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ResourceQueryLoader
{
    protected Builder|null $builder;

    public function __construct(?Builder $builder)
    {
        $this->builder = $builder;

    }

    public function load($columns = ['*']): Collection|LengthAwarePaginator|array
    {
        if (!($this->builder instanceof Builder)) {
            return [];
        }
        $per_page = (int)request()->query('per_page', 15);
        $paginate = (bool)request()->query('paginate', true);
        $ownership = request()->query('ownership');

        $search = request()->input('search', []);
        $this->search($search);
        $filters = request()->input('filters', []);
        if ($filters) {
            $this->filter($filters);
        }
        return $paginate ? $this->builder->orderByDesc('created_at')->paginate($per_page, $columns)
            : $this->builder->orderByDesc('created_at')->get($columns);
    }


    /**
     * @param mixed $search
     * @return void
     */
    private function search(mixed $search)
    {
        $queries = (!is_array($search)) ? json_decode($search, true) : $search;
        if ($queries != null && count($queries) > 0) {
            $this->builder = $this->builder->where(function ($builder) use ($queries) {
                $i = 0;
                foreach ($queries as $k => $query) {
                    if ($i == 0) {
                        if ($k != '_id') {
                            $builder->where($k, 'LIKE', '%' . $query . '%');
                        } else {
                            $builder->where($k, $query);
                        }
                    } else {
                        if ($k != '_id') {
                            $builder->orWhere($k, 'LIKE', '%' . $query . '%');
                        } else {
                            $builder->orWhere($k, $query);
                        }
                    }
                    $i++;
                }
            });
        }
        $term = request()->query('term');
        if (isset($term)) {
            $columns = $this->builder->getModel()::class::search() ?? [];
            if (count($columns)) {
                $this->builder->where(function (Builder $query) use ($columns, $term) {
                    foreach ($columns as $k => $column) {
                        if ($k == 0) {
                            $query->where($column, 'LIKE', '%' . $term . '%');
                        } else {
                            $query->orWhere($column, 'LIKE', '%' . $term . '%');
                        }
                    }
                });
            }
        }
    }

    /**
     * @param $filters
     * @return void
     */
    private function filter($filters)
    {
        $queries = (!is_array($filters)) ? json_decode($filters, false) : $filters;
        if ($queries !== null && count($queries) > 0) {
            $this->builder = $this->builder->where(function (Builder $builder) use ($queries) {
                foreach ($queries as $query) {
                    $field = $query['field'] ?? null;
                    $operator = $query['operator'] ?? '=';
                    $value = $query['value'] ?? null;
                    if ($field != null) {
                        if ($field != '_id') {
                            if (Str::upper($operator) == 'LIKE') {
                                $builder->where($field, 'LIKE', '%' . $value . '%');
                            } else {
                                $builder->where($field, $operator, $value);
                            }
                        } else {
                            $builder->where('_id', '=', $value);
                        }
                    }
                }
            });
        }
    }

}
