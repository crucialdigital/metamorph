<?php

namespace CrucialDigital\Metamorph\Http\Controllers;


use CrucialDigital\Metamorph\Config;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Model;

class MetamorphFormResourcesController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function entities(): JsonResponse
    {
        $r = collect(Config::resources());
        $r = array_values($r->sortBy('label')->toArray());
        return response()->json($r);
    }

    /**
     * @throws Exception
     */
    public function fetchResources($entity): JsonResponse
    {

        $model = Config::models($entity);
        $repository = Config::repositories($entity);

        $repository = class_exists($repository) ? (new $repository)->builder() : $model::where('id', 'exists', true);

        if (class_exists($model) && method_exists($model, 'label')) {
            $data = $this->load($repository, $model::searchField());
            return response()->json($this->transform($data, $model::label(), $model::labelValue()));
        } else {
            return response()->json();
        }
    }

    /**
     * @param $builder
     * @param array $search
     * @return \Illuminate\Database\Eloquent\Collection|LengthAwarePaginator|array
     * @throws Exception
     */

    private function load($builder, array $search = []): Collection|LengthAwarePaginator|array
    {
        $term = request()->input('term');
        if (isset($term)) {
            $query = [];
            foreach ($search as $item) {
                $query[] = [
                    'value' => $term,
                    'operator' => 'LIKE',
                    'field' => $item,
                    'coordinator' => 'or',
                    'group' => 'and_searchGroup',
                ];
            }
            $filters = request()->input('filters', []);
            request()->merge(['filters' => array_merge($query, $filters)]);
        }
        request()->merge(['term' => null]);
        request()->query->add(['paginate' => false]);
        return new ResourceQueryLoader($builder)->load($search);
    }

    /**
     * @param Collection|array $collection
     * @param string $label
     * @param string $labelValue
     * @return Collection
     */
    private function transform(Collection|array $collection, string $label, string $labelValue): Collection
    {
        return collect($collection)->map(function (Model $item) use ($label, $labelValue) {
            return [
                'value' => $item->getAttribute($labelValue) ?? $item->getAttribute('id'),
                'label' => $this->getAttribute($item, $label),
            ];
        });
    }

    private function getAttribute(Model $model, $key)
    {
        if (!Str::contains($key, '.')) {
            return $model->toArray()[$key] ?? '-';
        } else {
            $value = $model->toArray();
            $parts = explode('.', $key);
            foreach ($parts as $part) {
                $value = $value[$part] ?? $value;
            }
            return $value;
        }
    }
}
