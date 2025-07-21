<?php

namespace PDGIOnline\Auth\Facades;

use Illuminate\Support\Facades\Facade;

class PDGIAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pdgi-auth';
    }
}
