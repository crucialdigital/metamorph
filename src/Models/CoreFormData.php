<?php

namespace CrucialDigital\Metamorph\Models;

use Jenssegers\Mongodb\Relations\BelongsTo;

/**
 * @property mixed $Form
 */
class CoreFormData extends BaseModel
{
    public function Form(): BelongsTo
    {
        return $this->belongsTo(CoreForm::class, 'form_id', '_id');
    }

    public static function search(): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'name';
    }
}
