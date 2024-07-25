<?php

namespace CrucialDigital\Metamorph\Models;


use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property Collection $inputs
 * @property string $entity
 * @property bool $readOnly
 */

class MetamorphForm extends BaseModel
{
    protected $guarded = ['_id', 'inputs'];

    protected $with = ['inputs'];

    protected $appends = ['generate_columns'];


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

    public function generateColumns(): Attribute
    {
        return Attribute::get(function () {
            if ($this->getAttribute('columns')) {
                return $this->getAttribute('columns');
            } else {
                $columns = $this->inputs->map(fn($input) => $input->field)->take(5);
                return $columns->toArray();
            }
        });
    }
}
