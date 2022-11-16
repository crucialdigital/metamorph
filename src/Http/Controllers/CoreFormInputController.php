<?php

namespace CrucialDigital\Metamorph\Http\Controllers;


use CrucialDigital\Metamorph\Http\Requests\StoreCoreFormInputRequest;
use CrucialDigital\Metamorph\Models\CoreFormInput;
use Illuminate\Http\JsonResponse;

class CoreFormInputController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCoreFormInputRequest $request
     * @return JsonResponse
     */
    public function store(StoreCoreFormInputRequest $request): JsonResponse
    {
        $input = CoreFormInput::updateOrCreate([
            'field' => $request->input('field'),
            'form_id' => $request->input('form_id')
        ], $request->all());
        return response()->json($input->fresh());
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCoreFormInputRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(StoreCoreFormInputRequest $request, $id): JsonResponse
    {
        $input = CoreFormInput::findOrFail($id);
        $input->fill($request->all())->save();
        return response()->json($input->fresh());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $coreFormInput = CoreFormInput::findOrFail($id);
        $coreFormInput->delete();
        return response()->json($coreFormInput);
    }
}
