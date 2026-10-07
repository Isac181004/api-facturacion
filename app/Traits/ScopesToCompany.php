<?php

namespace App\Traits;

use App\Scopes\CompanyTenantScope;

trait ScopesToCompany
{
    protected static function bootScopesToCompany(): void
    {
        static::addGlobalScope(new CompanyTenantScope());
    }
}
