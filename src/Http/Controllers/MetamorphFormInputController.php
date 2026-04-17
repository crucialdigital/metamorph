<?php

namespace CrucialDigital\Metamorph\Http\Controllers;

use CrucialDigital\Metamorph\Http\Requests\StoreMetamorphFormInputRequest;
use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\Models\MetamorphFormInput;
use Illuminate\Http\JsonResponse;

class MetamorphFormInputController extends Controller
{
    /**
     * Store (or update) a form input in both storage locations:
     *   1. The MetamorphFormInput collection (for API CRUD)
     *   2. The embedded inputs array on the parent MetamorphForm (zero-query reads)
     */
    public function store(StoreMetamorphFormInputRequest $request): JsonResponse
    {
        $input = MetamorphFormInput::updateOrCreate(
            [
                'field'   => $request->input('field'),
                'form_id' => $request->input('form_id'),
            ],
            $request->all()
        );

        $this->syncEmbedded($request->input('form_id'));

        return response()->json($input->fresh());
    }

    /**
     * Update a form input and keep the embedded array in sync.
     */
    public function update(StoreMetamorphFormInputRequest $request, $id): JsonResponse
    {
        $input = MetamorphFormInput::findOrFail($id);
        $input->fill($request->all())->save();

        $this->syncEmbedded($input->form_id);

        return response()->json($input->fresh());
    }

    /**
     * Show a single form input.
     */
    public function show($id): JsonResponse
    {
        $input = MetamorphFormInput::findOrFail($id);
        return response()->json($input);
    }

    /**
     * Delete a form input and sync the embedded array on the parent form.
     */
    public function destroy($id): JsonResponse
    {
        $input = MetamorphFormInput::findOrFail($id);
        $formId = $input->form_id;

        $input->delete();

        $this->syncEmbedded($formId);

        return response()->json($input);
    }

    /**
     * Rebuild the embedded inputs array on the MetamorphForm document from the
     * MetamorphFormInput collection.  Called after every store / update / destroy
     * so that the two storage locations never drift apart.
     *
     * This is ONE extra write per mutation, but it eliminates the always-on
     * eager-load query ($with = ['inputs']) that previously ran on EVERY read.
     */
    protected function syncEmbedded(?string $formId): void
    {
        if (!$formId) return;

        $form = MetamorphForm::find($formId);
        if (!$form) return;

        $inputs = $form->formInputs()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($i) => $i->toArray())
            ->toArray();

        $form->setAttribute('inputs', $inputs);
        $form->save();
    }
}
