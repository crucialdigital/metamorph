<?php

namespace CrucialDigital\Metamorph\Http\Requests;

use CrucialDigital\Metamorph\Models\MetamorphForm;
use CrucialDigital\Metamorph\Rules\GeoPointRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreMasterUpdateFormRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $formRequest = [
            'form_id' => ['required', 'string', 'exists:metamorph_forms,_id'],
        ];
        $form = MetamorphForm::findOrFail($this->input('form_id'));
        $inputs = [];

        if ($form && $form->getAttribute('inputs')) {
            $inputs = $form->getAttribute('inputs');
        } else {
            abort(422, 'No field found !');
        }

        $this->merge(['entity' => $form->getAttribute('entity')]);


        $type_match = [
            'text' => ['string'],
            'longtext' => ['string'],
            'select' => ['string'],
            'multiselect' => ['array'],
            'resource' => ['string'],
            'number' => ['numeric'],
            'tel' => ['string'],
            'email' => ['string', 'email'],
            'date' => ['date'],
            'photo' => (Str::lower($this->method) == 'POST'
                && ($this->input('_method') != 'PATCH'
                    && $this->input('_method') != 'PUT')) ? ['file'] : [],
            'file' => (Str::lower($this->method) == 'POST'
                && ($this->input('_method') != 'PATCH'
                    && $this->input('_method') != 'PUT')) ? ['file'] : [],
            'geopoint' => ['string'],
            'polygon' => ['array']
        ];

        foreach ($inputs as $input) {
            $rules = [];
            $rules[] = (isset($input['required']) && $input['required'] == true) ? 'filled' : 'nullable';

            if (isset($input['type']) && isset($type_match[$input['type']])) {
                $rules = [...$rules, ...$type_match[$input['type']]];
            }

            if (isset($input['type']) && $input['type'] == 'file') {
                $rules[] = 'max:' . (814 + 2097152) * 1.37;
            }

            if (isset($input['type']) && $input['type'] == 'photo') {
                $rules[] = 'max:' . (814 + 512000) * 1.37;
            }

            if (isset($input['type']) && $input['type'] == 'geopoint') {
                $rules[] = new GeoPointRule;
            }


            if (isset($input['min'])) $rules[] = 'min:' . $input['min'];
            if (isset($input['max'])) $rules[] = 'max:' . $input['max'];

            if (isset($input['type']) && $input['type'] == 'select' && is_array($input['options']) && count($input['options']) > 0) {
                $list = implode(',', $input['options']);
                $rules [] = 'in:' . $list;
            }
            $formRequest [$input['field']] = $rules;
        }
        return $formRequest;
    }

    public function attributes(): array
    {
        $attributes = [];
        $inputs = MetamorphForm::findOrFail($this->input('form_id'))?->getAttribute('inputs') ?? [];
        foreach ($inputs as $input) {
            $attributes[$input['field']] = Str::lower($input['name']);
        }

        return $attributes;
    }

}