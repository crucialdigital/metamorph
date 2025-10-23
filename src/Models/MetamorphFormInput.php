<?php

namespace CrucialDigital\Metamorph\Models;
/**
 * @property string|null $type
 * @property string|null $field
 * @property string|null $form_id
 * @property bool|null $metamorph_input
 */

class MetamorphFormInput extends BaseModel
{
    protected $attributes = [
        'required' => false,
        'type' => 'text'
    ];
    protected $guarded = ['id', 'id'];

    public static function searchField(): array
    {
        return ['name', 'field', 'label'];
    }

    public static function label(): string
    {
        return 'name';
    }
}
