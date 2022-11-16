<?php

namespace CrucialDigital\Metamorph\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoreFormRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:200'],
            'visibility' => ['nullable', 'string'],
            'readOnly' => ['nullable', 'boolean'],
            'owners' => ['nullable', 'array'],
            'entity' => ['required', 'string']
        ];
    }
}
