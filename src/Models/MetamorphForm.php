<?php

namespace CrucialDigital\Metamorph\Models;


use Illuminate\Support\Collection;
use Jenssegers\Mongodb\Relations\HasMany;

/**
 * @property Collection $inputs
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
