<?php

namespace Stokoe\FormsToWherever\Facades;

use Illuminate\Support\Facades\Facade;
use Stokoe\FormsToWherever\ConnectorManager;

class FormConnectors extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ConnectorManager::class;
    }
}
