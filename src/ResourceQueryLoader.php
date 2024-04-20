<?php


namespace CrucialDigital\Metamorph;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Model;

class ResourceQueryLoader
{
    protected Builder|\MongoDB\Laravel\Eloquent\Builder|Model|null $builder;

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
        $this->builder = $this->builder->orderBy($order_by, $order_direction);

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
    private function search(mixed $search)
    {
        $queries = (!is_array($search)) ? json_decode($search, true) : $search;
        $term = request()->query('term');
        if (isset($term)) {
            $columns = $this->builder->getModel()::class::search() ?? [];
            foreach ($columns as $column) {
                $queries[$column] = $term;
            }
        }
        if ($queries != null && count($queries) > 0) {
            $this->builder->where(function ($builder) use ($queries) {
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
        $queries = (!is_array($filters)) ? json_decode($filters, false) : $filters;
        if ($queries !== null && count($queries) > 0) {
            foreach ($queries as $query) {
                $field = $query['field'] ?? null;
                $operator = $query['operator'] ?? '=';
                $value = $query['value'] ?? null;
                if ($field != null) {
                    if (str_contains($field, '.')) {
                        $parts = explode('.', $field);
                        $last = array_pop($parts);
                        $relations = implode('.', $parts);
                        $this->builder->whereHas($relations, function (Builder $builder) use ($last, $operator, $value) {
                            $this->bindQuery($last, $operator, $value, $builder);
                        });
                    } else {
                        $this->bindQuery($field, $operator, $value);
                    }
                }
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
     * @param Builder|null $builder
     * @return void
     */
    protected function bindQuery($field, $operator, $value, Builder $builder = null)
    {
        if($builder == null){
            $builder = $this->builder;
        }
        switch (Str::upper($operator)) {
            case 'LIKE':
                $builder->where($field, 'LIKE', '%' . $value . '%');
                break;
            case 'IN':
                $value = is_array($value) ? $value : [$value];
                $builder->whereIn($field, $value);
                break;
            case 'NOTIN':
                $value = is_array($value) ? $value : [$value];
                $builder->whereNotIn($field, $value);
                break;
            case 'BETWEEN':
                $value = is_array($value) ? $value : [$value, $value];
                $builder->whereBetween($field, $value);
                break;
            case 'NOTBETWEEN':
                $value = is_array($value) ? $value : [$value, $value];
                $builder->whereNotBetween($field, $value);
                break;
            case 'DATE':
                $builder->whereDate($field, '=', $value);
                break;
            case 'DATEBEFORE':
                $builder->whereDate($field, '<', $value);
                break;
            case 'DATEAFTER':
                $builder->whereDate($field, '>', $value);
                break;
            case 'DATEBEFOREQ':
                $builder->whereDate($field, '<=', $value);
                break;
            case 'DATEAFTEREQ':
                $builder->whereDate($field, '>=', $value);
                break;
            case 'DATENOT':
                $builder->whereDate($field, '!=', $value);
                break;
            case 'DATEBETWEEN':
                $value = $this->getDateArrayValue($value);
                $builder->whereBetween($field, $value);
                break;
            case 'DATENOTBETWEEN':
                $value = $this->getDateArrayValue($value);
                $builder->whereNotBetween($field, $value);
                break;
            default:
                $builder->where($field, $operator, $value);
        }
    }

}
