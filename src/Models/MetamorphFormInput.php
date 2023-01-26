<?php

namespace CrucialDigital\Metamorph\Models;

class MetamorphFormInput extends BaseModel
{
    protected $attributes = [
        'required' => false,
        'type' => 'text'
    ];
    protected $fillable = [
        'field', 'name', 'label', 'placeholder', 'type', 'options', 'entity', 'width',
        'default', 'required', 'readOnly', 'depends', 'operator', 'needle', 'form_id'
    ];

    public static function search(): array
    {
        return ['name', 'field', 'label'];
    }

    public static function label(): string
    {
        return 'name';
    }
}
