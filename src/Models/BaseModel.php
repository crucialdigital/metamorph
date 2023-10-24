<?php

namespace CrucialDigital\Metamorph\Models;


use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property-read string $_id
 * @method static Model|self first()
 * @method static findOrFail(string $id)
 * @method static Model firstOrCreate(array $search, array $attributes = [])
 * @method static Model find(string $id)
 * @method static Model updateOrCreate(array $search, array $attributes = [])
 * @method static Model create(array $attributes)
 * @method static Builder|Model where(string $column, string $operator='=', mixed $value='')
 */
abstract class BaseModel extends Model
{

    protected $guarded = ['_id'];
    protected $appends = ['id'];

    protected $dates = ['created_at', 'update_at'];

    public static abstract function search(): array;

    public static abstract function label(): string;

    public static  function labelValue(): string
    {
        return '_id';
    }

    public static function extraFields(): array
    {
        return [];
    }

}
