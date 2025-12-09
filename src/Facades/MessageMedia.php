<?php

namespace IxaDevStuff\MessageMedia\Facades;

use Illuminate\Support\Facades\Facade;

class MessageMedia extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'messagemedia';
    }
}
