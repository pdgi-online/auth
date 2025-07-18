<?php

namespace PDGIOnline\PDGIAuthClient\Facades;

use Illuminate\Support\Facades\Facade;

class PDGIAuth extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pdgi-auth';
    }
}