<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use Countable;
use CrucialDigital\Metamorph\Config;
use CrucialDigital\Metamorph\Http\Requests\StoreMasterStoreFormRequest;
use CrucialDigital\Metamorph\Http\Requests\StoreMasterUpdateFormRequest;
use CrucialDigital\Metamorph\Metamorph;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Builder;

class MasterCrudController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        $middlewares = [];
        $model = request()->route('entity');
        $middlewares_config = Config::modelMiddleware($model);
        if (isset($middlewares_config[$model])) {
            foreach ($middlewares_config[$model] as $middleware => $only) {
                if (is_string($only) && $only == '*') {
                    $middlewares[] = new Middleware($middleware);
                } else {
                    if (is_array($only)) {
                        $middlewares[] = new Middleware($middleware, only:  $only);
                    }
                }
            }
        }
        Log::debug($middlewares);
        return $middlewares;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMasterStoreFormRequest $request
     * @param string $model
     * @return JsonResponse
     */
    public function store(StoreMasterStoreFormRequest $request, string $model): JsonResponse
    {

        $policies = collect(Config::policies($model))->map(fn($police) => Str::lower($police))->toArray();

        if (in_array('create', $policies)) {
            Gate::authorize("create", config("metamorph.models.$model"));
        }

        $formData = Metamorph::mapFormRequestData($request->all());

        $entity = config('metamorph.models.' . $model)::create($formData);

        if ($entity && $entity->_id) {
            $entity->fill(Metamorph::mapFormRequestFiles(
                $request,
                $entity->_id,
                $request->input('form_id')
            ))->save();
        }

        return response()->json($entity->fresh());

    }

    /**
     * Display the specified resource.
     *
     * @param string $model
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $model, string $id): JsonResponse
    {
        /**
         * @var Builder $data
         */
        $data = Config::models($model)::where('_id', '=', $id);
        $with = ResourceQueryLoader::makeRelations($data);
        if ($with != null) $data = $data->with($with);
        $data = $data->firstOrFail();

        $policies = collect(Config::policies($model))->map(fn($police) => Str::lower($police))->toArray();

        if (in_array('view', $policies)) {
            Gate::authorize("view", $data);
        }

        $form = MetamorphForm::where('entity', $model)->latest()->first();
        $inputs = $form?->getAttribute('inputs');
        if ($inputs) {
            $metas = collect($inputs)
                ->filter(function ($input) {
                    return in_array($input['type'], ['resource', 'multiresource', 'selectresource']);
                })->map(function ($el) use ($data) {
                    try {
                        $res = Config::models($el['entity'])::find($data[$el['field']]);
                    } catch (Exception $e) {
                        $res = null;
                    }
                    if ($res instanceof Countable) {
                        $value = join(', ', collect($res)->map(function ($entry) use ($el) {
                            return $entry->getAttribute(Config::models($el['entity'])::label());
                        })->values()->toArray());
                    }else{
                        $value = $res ? $res->getAttribute(Config::models($el['entity'])::label()) : '';
                    }
                    return [
                        'label' => $el['field'],
                        'value' => $value
                    ];
                });

            $data['meta_data'] = array_values($metas->toArray());
        }
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreMasterUpdateFormRequest $request
     * @param string $model
     * @param string $id
     * @return JsonResponse
     */
    public function update(StoreMasterUpdateFormRequest $request, string $model, string $id): JsonResponse
    {
        $entity = Config::models($model)::findOrFail($id);
        $policies = collect(Config::policies($model))->map(fn($police) => Str::lower($police))->toArray();

        if (in_array('update', $policies)) {
            Gate::authorize("update", $entity);
        }
        $data = $request->all();
        $formData = Metamorph::mapFormRequestData($data);
        $files = Metamorph::mapFormRequestFiles($request, $id, $request->input('form_id'));

        $entity->fill($formData)->fill($files)->save();

        return response()->json($entity->fresh());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $model
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $model, string $id): JsonResponse
    {

        $data = Config::models($model)::findOrFail($id);

        $policies = collect(Config::policies($model))->map(fn($police) => Str::lower($police))->toArray();

        if (in_array('delete', $policies)) {
            Gate::authorize("delete", $data);
        }

        $data?->delete();
        return response()->json($data);
    }
}
