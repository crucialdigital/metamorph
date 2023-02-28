<?php

namespace CrucialDigital\Metamorph;


use \Illuminate\Database\Eloquent\Builder;

abstract class DataRepositoryBuilder
{
    public abstract function builder(): ?Builder;
}
