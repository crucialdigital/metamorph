<?php

namespace CrucialDigital\Metamorph\Http\Controllers;


use CrucialDigital\Metamorph\Http\Requests\StoreMasterCrudFormRequest;
use CrucialDigital\Metamorph\Metamorph;
use CrucialDigital\Metamorph\Models\MetamorphFormData;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetamorphFormDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $coreFormData = MetamorphFormData::query();
        $form = $request->query('form');
        $rejected = (bool)$request->query('rejected');

        if ($rejected == true) {
            $coreFormData = $coreFormData->where('rejected', '=', true);
        }

        if ($form) {
            $coreFormData = $coreFormData->where('form_id', $form);
        }

        $coreFormData = (new ResourceQueryLoader($coreFormData))->load();
        return response()->json($coreFormData);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMasterCrudFormRequest $request
     * @return JsonResponse
     */
    public function store(StoreMasterCrudFormRequest $request): JsonResponse
    {

        $data = Metamorph::mapFormRequestData($request->all());
        $data['rejected'] = false;
        $entity = MetamorphFormData::create($data);
        $entity?->fill(Metamorph::mapFormRequestFiles($request, $entity->_id, $request->input('form_id')))->save();

        return response()->json($entity->fresh());
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $coreFormData = MetamorphFormData::find($id);
        return response()->json($coreFormData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreMasterCrudFormRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(StoreMasterCrudFormRequest $request, $id): JsonResponse
    {
        $coreFormData = MetamorphFormData::findOrFail($id);
        $data = Metamorph::mapFormRequestData($request->all());
        $data['rejected'] = false;
        $coreFormData = $coreFormData->fill($data);
        $coreFormData->fill(Metamorph::mapFormRequestFiles($request, $coreFormData->_id, $request->input('form_id')))->save();
        return response()->json($coreFormData->fresh());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $coreFormData = MetamorphFormData::find($id);
        $coreFormData->delete();
        return response()->json($coreFormData);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function rejectFormData(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rejection_observations' => ['required', 'string', 'min:20']
        ]);
        $coreFormData = MetamorphFormData::findOrFail($id);

        $coreFormData->fill(['rejected' => true, 'rejection_observations' => $request->input('rejection_observations')])->save();
        return response()->json($coreFormData);
    }

    /**
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function validateFormData(Request $request, $id): JsonResponse
    {
        $data = MetamorphFormData::findOrFail($id);
        $model = config('metamorph.entity.' . $data['entity']);
        $data = array_merge($data->toArray(), $request->all());

        try {
            $data = $model::create($data);
            if ($data) {
                MetamorphFormData::destroy($id);
            }
            return response()->json($data);
        } catch (\Exception $exception) {
            return response()->json($exception->getMessage());
        }
    }
}
