<?php

namespace Clickonmedia\Peach\Facades;

use Illuminate\Support\Facades\Facade;

class Peach extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'peach';
    }
}
