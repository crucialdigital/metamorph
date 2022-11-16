<?php

namespace CrucialDigital\Metamorph\Models;


use Jenssegers\Mongodb\Eloquent\Model;

/**
 * @property-read string $_id
 * @method static Model findOrFail(string $id)
 * @method static Model firstOrCreate(array $search, array $attributes = [])
 * @method static Model find(string $id)
 * @method static Model updateOrCreate(array $search, array $attributes = [])
 * @method static Model create(array $attributes)
 */
abstract class BaseModel extends Model
{
    protected $guarded = ['_id'];
    protected $appends = ['id'];

    protected $dates = ['created_at', 'update_at'];

    public static abstract function search(): array;
    public static abstract function label(): string;
}
