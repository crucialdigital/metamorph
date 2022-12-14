<?php

namespace CrucialDigital\Metamorph;

use Countable;
use CrucialDigital\Metamorph\Models\CoreForm;
use Illuminate\Http\Request;

class Metamorph
{
    public static function mapFormRequestData(Countable|array $form_data): array
    {
        $form = CoreForm::findOrFail($form_data['form_id']);

        $form_inputs = $form['inputs'];
        if (!isset($form_data)) return [];


        $rtr = ['form_id' => $form->getAttribute('_id')];
        $rtr['entity'] = $form_data['entity'];

        foreach ($form_data as $k => $datum) {
            $self_input = collect($form_inputs)->firstWhere('field', '=', $k);
            if (isset($self_input)
                && isset($self_input['type'])
                && $self_input['type'] != 'file'
                && $self_input['type'] != 'photo'
                && isset($datum)) {
                $rtr[$k] = $datum;
            }
        }
        return $rtr;
    }

    public static function mapFormRequestFiles(Request $request, string $entity_id, string $form_id): array
    {
        $return = [];
        $form = CoreForm::find($form_id);
        if (!isset($form)) return $return;
        $form_inputs = $form['inputs'] ?? [];

        foreach ($form_inputs as $input) {
            if (isset($input['field']) && isset($input['type']) && in_array($input['type'], ['file', 'photo'])) {
                if ($request->hasFile($input['field']) && $request->file($input['field'])->isValid()) {
                    $path = $form['formType'] . '/' . $input['field'] . '/';
                    if ($request->file($input['field'])->storePubliclyAs($path, $entity_id . '.' . $request->file($input['field'])->getClientOriginalExtension())) {
                        $return[$input['field']] = $path . $entity_id . '.' . $request->file($input['field'])->getClientOriginalExtension();
                    } else {
                        \Illuminate\Support\Facades\Log::error('Can\'t store');
                    }
                } else {
                    \Illuminate\Support\Facades\Log::error('Invalid file');
                }
            }
        }

        return $return;
    }

    public static function mergeFormFields($entity, array $default = []): array
    {
        $forms = CoreForm::query()->where('formType', $entity)->get(['inputs']);
        $fields = ['_id', ...$default];
        foreach ($forms as $form) {
            if (isset($form['inputs'])) {
                foreach ($form['inputs'] as $input) {
                    if (isset($input['field']) && !in_array($input['field'], $fields)) {
                        $fields [] = $input['field'];
                    }
                }
            }
        }
        return $fields;
    }
}
