<?php

namespace CrucialDigital\Metamorph\Models;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Relations\EmbedsMany;
use MongoDB\Laravel\Relations\EmbedsOneOrMany;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * @property-read string $_id
 * @property string $ref
 * @method static Model|self first()
 * @method static findOrFail(string $id)
 * @method static Model firstOrCreate(array $search, array $attributes = [])
 * @method static Model|mixed find(string $id)
 * @method static Model updateOrCreate(array $search, array $attributes = [])
 * @method static Model create(array $attributes)
 * @method static Builder|Model where(string $column, string $operator = '=', mixed|null $value = null)
 * @method static Builder|Model whereIn(string $column, array $value = [])
 * @method static Builder|Model orderBy(string $column, string $direction)
 */
abstract class BaseModel extends Model
{

    public $timestamps = true;
    protected $guarded = ['_id'];
    protected $appends = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'update_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public static abstract function search(): array;

    public static abstract function label(): string;

    public static function labelValue(): string
    {
        return '_id';
    }

    public static function exportsFields(): array
    {
        return [];
    }

    public static function extraFields(): array
    {
        return [];
    }

    public function nextId()
    {
        // ref is the counter - change it to whatever you want to increment
        $this->ref = self::getID($this->getTable());
    }

    public static function bootUseAutoIncrementID()
    {
        static::creating(function ($model) {
            $model->sequencial_id = self::getID($this->getTable());
        });
    }

    public function getCasts(): array
    {
        return $this->casts;
    }

    private static function getID($ref)
    {
        $seq = DB::connection(config('database.default'))->getCollection('counters')->findOneAndUpdate(
            ['ref' => $ref],
            ['$inc' => ['seq' => 1]],
            ['new' => true, 'upsert' => true, 'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );
        return $seq->seq;
    }

    /**
     * Added serialization for embedded documents.
     *
     * @inheritDoc
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($attributes as $key => $value) {
            if ($this->isEmbedsRelationship($key)) {
                $attributes[$key] = $this->serializeEmbedded($key);
            }
        }

        return $attributes;
    }

    /**
     * Determine if the given key belongs to an embeds one or an embeds many relationship.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isEmbedsRelationship(string $key): bool
    {
        return method_exists($this, $key) && $this->getAttribute($key) instanceof EmbedsOneOrMany;
    }

    /**
     * Serialize all embedded models to apply all mutations defined in the model class and
     * to serialize the MongoId to string.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function serializeEmbedded(string $key): mixed
    {
        $data = $this->getAttribute($key);
        $model = $this->$key()->getRelated();

        if ($data instanceof EmbedsMany) {
            return $model::hydrate($data->toArray());
        }

        $attribute = $this->getAttribute($key);

        return ((new $model)->newInstance($attribute, true))->toArray();
    }

    public function getTable(): string
    {
        return $this->collection ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }
}
