<?php

namespace CrucialDigital\Metamorph\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMetamorphFormInputRequest extends FormRequest
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
            'field' => ['required', 'string'],
            'form_id' => ['required', 'string', 'exists:metamorph_forms,_id'],
            'name' => ['required', 'string'],
            'width' => ['nullable', 'number', 'min:1', 'max:24'],
            'label' => ['nullable', 'string', 'min:2'],
            'type' => ['required', 'string', 'in:text,longtext,select,multiselect,multiresource,resourceselect,resource,number,tel,email,date,photo,file,geopoint,polygon'],
            'required' => ['nullable', 'boolean'],
            'options' => ['required_if:type,select,multiselect,polygon', 'nullable', 'array'],
            'entity' => ['required_if:type,resource,multiresource,resourceselect'],
            'placeholder' => ['nullable', 'string']
        ];
    }
}
