<?php

declare(strict_types=1);

namespace Pepperfm\DonationalertsAuth\Facades;

use Illuminate\Support\Facades\Facade;

class DonationalertsAuthFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Pepperfm\DonationalertsAuth\Contracts\DonationalertsAuthContract::class;
    }
}
