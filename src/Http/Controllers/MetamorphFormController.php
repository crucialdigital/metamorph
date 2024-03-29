<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\Http\Requests\StoreMetamorphFormRequest;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MetamorphFormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $coreForm = MetamorphForm::where('_id', 'exists', true);
        if ($request->has('type') && $request->input('type') !== null) {
            $coreForm = $coreForm->where('entity', $request->query('type'));
        }
        $coreForm = (new ResourceQueryLoader($coreForm))->load();
        return response()->json($coreForm);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMetamorphFormRequest $request
     * @return JsonResponse
     */
    public function store(StoreMetamorphFormRequest $request): JsonResponse
    {

        $form = MetamorphForm::updateOrCreate([
            'name' => $request->input('name'),
            'entity' => $request->input('entity'),
            'owner_id' => Auth::id()
        ], [
            'owners' => $request->input('owners'),
            'readOnly' => $request->input('readOnly', true),
            'visibility' => $request->input('visibility'),
        ]);

        if ($form == null) return abort(500);
        return response()->json($form->fresh());
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        return response()->json(MetamorphForm::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreMetamorphFormRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(StoreMetamorphFormRequest $request, $id): JsonResponse
    {
        $coreForm = MetamorphForm::findOrFail($id);
        $coreForm->update($request->only(['name', 'visibility', 'owners', 'readOnly']));
        return response()->json($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $coreForm = MetamorphForm::findOrFail($id);
        $coreForm->delete();
        return response()->json($coreForm);
    }

    /**
     * @param $entity
     * @return JsonResponse
     */
    public function get_form_by_entity($entity): JsonResponse
    {
        $form = MetamorphForm::where('entity', $entity)
            ->latest()
            ->first();
        return $form != null ? response()->json($form) : abort(404);
    }
}
