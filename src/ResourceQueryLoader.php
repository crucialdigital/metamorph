<?php


namespace CrucialDigital\Metamorph;


use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ResourceQueryLoader
{
    protected Builder $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;

    }

    public function load($columns = ['*']): Collection|LengthAwarePaginator|array
    {
        $per_page = (int)request()->query('per_page', 15);
        $paginate = (bool)request()->query('paginate', true);
        $ownership = request()->query('ownership');

        $search = request()->input('search');
        if ($search) {
            $this->search($search);
        }
        $filters = request()->input('filters');
        if ($filters) {
            $this->filter($filters);
        }

        if ($ownership && $ownership != '') {
            $user = Auth::user();
            $this->builder = $this->builder->where(function (Builder $b) use ($ownership, $user) {
                $b->where($ownership, Auth::id())->orWhere($ownership, $user['actor_id']);
            });
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
    }

    /**
     * @param $filters
     * @return void
     */
    private function filter($filters)
    {
        $queries = (!is_array($filters)) ? json_decode($filters, true) : $filters;
        if ($queries != null && count($queries) > 0) {
            $this->builder = $this->builder->where(function (Builder $builder) use ($queries) {
                foreach ($queries as $query) {
                    $field = $query['field'] ?? null;
                    $operator = $query['operator'] ?? '=';
                    $value = $query['value'] ?? null;
                    if ($field != null) {
                        if ($field != '_id') {
                            if (Str::upper($operator) == 'LIKE') {
                                $builder->where($field, $operator, '%' . $value . '%');
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
