<?php

namespace CrucialDigital\Metamorph\Http\Controllers;


use CrucialDigital\Metamorph\ResourceQueryLoader;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Jenssegers\Mongodb\Eloquent\Model;

class MetamorphFormResourcesController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function entities(): JsonResponse
    {
        $r = collect(config('metamorph.resources', []));
        $r = array_values($r->sortBy('label')->toArray());
        return response()->json($r);
    }

    public function fetchResources(Request $request, $entity): JsonResponse
    {

        $model = config('metamorph.models.' . $entity);
        if (class_exists($model) && method_exists($model, 'label')) {
            $data = $model::query();
            $data = $this->load($request, $data, $model::search());
            return response()->json($this->transform($data, $model::label()));
        } else {
            return response()->json([]);
        }
    }

    /**
     * @param Request $request
     * @param $builder
     * @param array $search
     * @return \Illuminate\Database\Eloquent\Collection|LengthAwarePaginator|array
     */

    private function load(Request $request, $builder, array $search = []): Collection|LengthAwarePaginator|array
    {
        if ($request->has('term') && $request->input('term') != null) {
            $query = [];
            foreach ($search as $item) {
                $query[$item] = $request->input('term');
            }
            $request->merge(['search' => $query]);
        }
        $request->query->add(['paginate' => false]);
        return (new ResourceQueryLoader($builder))->load($search);
    }

    /**
     * @param Collection $collection
     * @param string $attribute
     * @return Collection
     */
    private function transform(Collection $collection, string $attribute): Collection
    {
        return $collection->map(function (Model $item) use ($attribute) {
            return [
                'value' => $item->getAttribute('_id'),
                'label' => $this->getAttribute($item, $attribute),
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
