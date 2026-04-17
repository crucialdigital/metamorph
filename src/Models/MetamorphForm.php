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
    /**
     * 'inputs' is stored as an embedded array field directly on the MongoDB document.
     * This avoids an extra query to metamorph_form_inputs on every form lookup.
     */
    protected $guarded = ['id'];

    protected $appends = ['generate_columns'];

    protected $casts = [
        'readOnly' => 'boolean',
        'inputs'   => 'array',  // Embedded — loaded with the parent document, zero extra query
    ];

    /**
     * Relation kept for the form-inputs CRUD API (MetamorphFormInputController).
     * Do NOT use this to read inputs at request time — use $form->inputs (cast array) instead.
     *
     * @return HasMany
     */
    public function formInputs(): HasMany
    {
        return $this->hasMany(MetamorphFormInput::class, 'form_id', 'id');
    }

    /**
     * @return string[]
     */
    public static function searchField(): array
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
            }
            // inputs is now a plain array (cast), not a Collection of model objects
            return collect($this->getAttribute('inputs') ?? [])
                ->map(fn($input) => $input['field'] ?? null)
                ->filter()
                ->take(5)
                ->values()
                ->toArray();
        });
    }
}
