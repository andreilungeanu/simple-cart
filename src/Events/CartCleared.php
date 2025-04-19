<?php

namespace AndreiLungeanu\SimpleCart\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartCleared
{
    use Dispatchable, SerializesModels;

    public function __construct() {}
}
