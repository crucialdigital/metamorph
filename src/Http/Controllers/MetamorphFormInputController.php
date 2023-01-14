<?php

namespace CrucialDigital\Metamorph\Http\Controllers;


use CrucialDigital\Metamorph\Http\Requests\StoreMetamorphFormInputRequest;
use CrucialDigital\Metamorph\Models\MetamorphFormInput;
use Illuminate\Http\JsonResponse;

class MetamorphFormInputController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMetamorphFormInputRequest $request
     * @return JsonResponse
     */
    public function store(StoreMetamorphFormInputRequest $request): JsonResponse
    {
        $input = MetamorphFormInput::updateOrCreate([
            'field' => $request->input('field'),
            'form_id' => $request->input('form_id')
        ], $request->all());
        return response()->json($input->fresh());
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param StoreMetamorphFormInputRequest $request
     * @param $id
     * @return JsonResponse
     */
    public function update(StoreMetamorphFormInputRequest $request, $id): JsonResponse
    {
        $input = MetamorphFormInput::findOrFail($id);
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
        $coreFormInput = MetamorphFormInput::findOrFail($id);
        $coreFormInput->delete();
        return response()->json($coreFormInput);
    }
}
