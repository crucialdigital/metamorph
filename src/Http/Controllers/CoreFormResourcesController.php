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

class CoreFormResourcesController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function entities(): JsonResponse
    {
        return response()->json(config('metamorph.resources', []));
    }

    public function fetchResources(Request $request, $entity): JsonResponse
    {

        $models = config('metamorph.models.' . $entity);

        try {
            $data = $models::query();
            $data = $this->load($request, $data, $models::search(), $models::label());

            return response()->json($this->transform($data, $models::label()));
        } catch (Exception $exception) {
            return response()->json($exception->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param $builder
     * @param array $search
     * @param string $label
     * @return \Illuminate\Database\Eloquent\Collection|LengthAwarePaginator|array
     */

    private function load(Request $request, $builder, array $search = [], string $label = '*'): Collection|LengthAwarePaginator|array
    {
        if ($request->has('term') && $request->input('term') != null) {
            $query = [];
            foreach ($search as $item) {
                $query[$item] = $request->input('term');
            }
            $request->merge(['search' => $query]);
        }
        $request->query->add(['paginate' => false]);
        return (new ResourceQueryLoader($builder))->load([$label]);
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
            return $model->toArray()[$key] ?? '';
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
