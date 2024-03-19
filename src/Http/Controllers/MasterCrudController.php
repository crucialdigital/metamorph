<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\Http\Requests\StoreMasterStoreFormRequest;
use CrucialDigital\Metamorph\Http\Requests\StoreMasterUpdateFormRequest;
use CrucialDigital\Metamorph\Metamorph;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use MongoDB\Laravel\Eloquent\Builder;

class MasterCrudController extends Controller
{

    public function __construct()
    {
        $model = request()->route('entity');
        $middlewares = config('metamorph.model_middlewares', []);
        if (isset($middlewares[$model])) {
            foreach ($middlewares[$model] as $middleware => $only) {
                if (is_string($only) && $only == '*') {
                    if (class_exists($middleware)) {
                        $this->middleware($middleware);
                    }
                } else {
                    if (class_exists($middleware) && is_array($only)) {
                        $this->middleware($middleware, ['only' => $only]);
                    }
                }
            }
        }
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

        $policies = config('metamorph.policies.' . $model, []);

        if (in_array('create', $policies)) {
            Gate::authorize("create $model");
        }

        $formData = Metamorph::mapFormRequestData($request->all());

        $entity = config('metamorph.models.' . $model)::create($formData);

        $entity?->fill(Metamorph::mapFormRequestFiles(
            $request,
            $entity->_id,
            $request->input('form_id')
        ))->save();

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

        $policies = config('metamorph.policies.' . $model, []);

        if (in_array('view', $policies)) {
            Gate::authorize("view $model");
        }
        /**
         * @var Builder $data
         */
        $data = config('metamorph.models.' . $model)::where('_id', '=', $id);
        $with = ResourceQueryLoader::makeRelations($data);
        if ($with != null) $data = $data->with($with);
        $data = $data->firstOrFail();

        $form = MetamorphForm::where('entity', $model)->latest()->first();
        $inputs = $form?->getAttribute('inputs');
        if ($inputs) {
            $metas = collect($inputs)
                ->filter(function ($input) {
                    return in_array($input['type'], ['resource', 'multiresource', 'selectresource']);
                })->map(function ($el) use ($data) {
                    try {
                        $res = config('metamorph.models.' . $el['entity'])::find($data[$el['field']]);
                    } catch (Exception $e) {
                        $res = null;
                    }
                    return [
                        'label' => $el['field'],
                        'value' => $res ? $res->getAttribute(config('metamorph.models.' . $el['entity'])::label()) : ''
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

        $policies = config('metamorph.policies.' . $model, []);

        if (in_array('update', $policies)) {
            Gate::authorize("update $model");
        }
        $entity = config('metamorph.models.' . $model)::findOrFail($id);
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

        $policies = config('metamorph.policies.' . $model, []);

        if (in_array('delete', $policies)) {
            Gate::authorize("delete $model");
        }

        $actor = config('metamorph.models.' . $model)::findOrFail($id);
        $actor?->delete();
        return response()->json($actor);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param string $model
     * @return JsonResponse
     */
    public function erase(Request $request, string $model): JsonResponse
    {

        $policies = config('metamorph.policies.' . $model, []);

        if (in_array('delete', $policies)) {
            Gate::authorize("delete $model");
        }
        $count = config('metamorph.models.' . $model)::destroy($request->input('ids', []));
        return response()->json($count);
    }
}
