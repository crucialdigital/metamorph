<?php


namespace CrucialDigital\Metamorph;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResourceQueryLoader
{
    protected Builder|\MongoDB\Laravel\Eloquent\Builder|null $builder;

    public function __construct(?Builder $builder)
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
        $order_direction = request()->query('order_direction', 'DESC');

        $paginate = (bool)request()->query('paginate', true);
        $randomize = (bool)request()->query('randomize', false);
        $with = is_array(request()->input('relations'))
            ? request()->input('relations')
            : null;

        $search = request()->input('search', []);
        $this->search($search);
        $filters = request()->input('filters', []);
        if ($filters) {
            $this->filter($filters);
        }
        $this->builder->orderBy($order_by, $order_direction);

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
            if($with != null) $data = $data->load($with);
            return $data;
        }else{
            if($with != null) $this->builder = $this->builder->with($with);
        }

        return $paginate
            ? $this->builder->paginate($per_page, $columns)
            : $this->builder->get($columns);
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
