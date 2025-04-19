<?php

namespace AndreiLungeanu\SimpleCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AndreiLungeanu\SimpleCart\SimpleCart
 */
class SimpleCart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AndreiLungeanu\SimpleCart\SimpleCart::class;
    }
}
