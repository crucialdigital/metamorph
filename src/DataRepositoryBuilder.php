<?php

namespace CrucialDigital\Metamorph;



use Illuminate\Http\Request;
use MongoDB\Laravel\Eloquent\Builder;
use MongoDB\Laravel\Eloquent\Model;

abstract class DataRepositoryBuilder
{
    public abstract function builder(): Builder|Model|null;
}
