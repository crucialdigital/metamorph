<?php

namespace CrucialDigital\Metamorph\Models;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property Collection $inputs
 * @property string $entity
 */

class MetamorphForm extends BaseModel
{
    protected $guarded = ['_id', 'inputs'];

    protected $with = ['inputs'];


    protected $casts = [
        'readOnly' => 'boolean',
    ];


    public function inputs(): HasMany
    {
        return $this->hasMany(MetamorphFormInput::class, 'form_id', '_id');
    }

    public static function search(): array
    {
        return ['name', 'entity'];
    }

    public static function label(): string
    {
        return 'name';
    }
}
