<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\Http\Requests\StoreMasterCrudFormRequest;
use CrucialDigital\Metamorph\Metamorph;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterCrudController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMasterCrudFormRequest $request
     * @param string $model
     * @return JsonResponse
     */
    public function store(StoreMasterCrudFormRequest $request, string $model): JsonResponse
    {

        $formData = Metamorph::mapFormRequestData($request->all());
        $formData['agent_id'] = Auth::id();

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
        $entity = config('metamorph.models.' . $model)::findOrFail($id);
        return response()->json($entity);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreMasterCrudFormRequest $request
     * @param string $model
     * @param string $id
     * @return JsonResponse
     */
    public function update(StoreMasterCrudFormRequest $request, string $model, string $id): JsonResponse
    {
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
        $count = config('metamorph.models.' . $model)::destroy($request->input('ids', []));
        return response()->json($count);
    }
}
