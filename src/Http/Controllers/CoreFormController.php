<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\Http\Requests\StoreCoreFormRequest;
use CrucialDigital\Metamorph\Models\CoreForm;
use CrucialDigital\Metamorph\ResourceQueryLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoreFormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $coreForm = CoreForm::query()->where('owner_id', Auth::id());
        if ($request->has('type') && $request->input('type') !== null) {
            $coreForm = $coreForm->where('entity', $request->query('type'));
        }
        $coreForm = (new ResourceQueryLoader($coreForm))->load();
        return response()->json($coreForm);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCoreFormRequest $request
     * @return JsonResponse
     */
    public function store(StoreCoreFormRequest $request): JsonResponse
    {

        $form = CoreForm::updateOrCreate([
            'name' => $request->input('name'),
            'entity' => $request->input('entity'),
            'owner_id' => Auth::id()
        ], [
            'owners' => $request->input('owners'),
            'readOnly' => $request->input('readOnly', true),
            'visibility' => $request->input('visibility'),
        ]);

        if (!$form) return abort(500);
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
        return response()->json(CoreForm::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param StoreCoreFormRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(StoreCoreFormRequest $request, $id): JsonResponse
    {
        $coreForm = CoreForm::query()->findOrFail($id);
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
        $coreForm = CoreForm::findOrFail($id);
        $coreForm->delete();
        return response()->json($coreForm);
    }
}
