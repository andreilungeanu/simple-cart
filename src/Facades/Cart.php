<?php

declare(strict_types=1);

namespace AndreiLungeanu\SimpleCart\Facades;

use AndreiLungeanu\SimpleCart\Services\CartService;
use Illuminate\Support\Facades\Facade;

class Cart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CartService::class;
    }
}
