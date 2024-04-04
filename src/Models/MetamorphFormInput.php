<?php

namespace CrucialDigital\Metamorph\Models;
/**
 * @property string|null $type
 * @property string|null $field
 */

class MetamorphFormInput extends BaseModel
{
    protected $attributes = [
        'required' => false,
        'type' => 'text'
    ];
    protected $guarded = ['_id', 'id'];

    public static function search(): array
    {
        return ['name', 'field', 'label'];
    }

    public static function label(): string
    {
        return 'name';
    }
}
