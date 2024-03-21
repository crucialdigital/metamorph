<?php

namespace CrucialDigital\Metamorph;



use Illuminate\Http\Request;
use MongoDB\Laravel\Eloquent\Builder;

abstract class DataRepositoryBuilder
{
    public abstract function builder(): ?Builder;
}
