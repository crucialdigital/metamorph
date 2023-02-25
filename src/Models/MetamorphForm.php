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

    /**
     * @return HasMany
     */
    public function inputs(): HasMany
    {
        return $this->hasMany(MetamorphFormInput::class, 'form_id', '_id');
    }

    /**
     * @return string[]
     */
    public static function search(): array
    {
        return ['name', 'entity'];
    }

    /**
     * @return string
     */
    public static function label(): string
    {
        return 'name';
    }
}
