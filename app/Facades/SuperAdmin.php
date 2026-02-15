<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class SuperAdmin extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \App\Services\SuperAdminService::class;
    }
}
