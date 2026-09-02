<?php

namespace App\Support;

use OwenIt\Auditing\Contracts\UserResolver;

class AuditUserResolver implements UserResolver
{
    public static function resolve()
    {
        return request()->user();
    }
}
