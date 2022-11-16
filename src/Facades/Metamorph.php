<?php

namespace CrucialDigital\Metamorph\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CrucialDigital\Metamorph\Metamorph
 */
class Metamorph extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \CrucialDigital\Metamorph\Metamorph::class;
    }
}
