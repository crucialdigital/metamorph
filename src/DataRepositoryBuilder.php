<?php

namespace CrucialDigital\Metamorph;



use MongoDB\Laravel\Eloquent\Builder;

abstract class DataRepositoryBuilder
{
    public abstract function builder(): ?Builder;
}
