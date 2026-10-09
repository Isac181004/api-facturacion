<?php

namespace App\Scopes;

use App\Models\Company;
use App\Models\Correlative;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(TenantContext::class)->companyId();

        if ($companyId === null) {
            return;
        }

        if ($model instanceof Company) {
            $builder->where($model->qualifyColumn('id'), $companyId);
            return;
        }

        if ($model instanceof Correlative) {
            $builder->whereHas('branch', fn (Builder $query) => $query->where('company_id', $companyId));
            return;
        }

        $builder->where($model->qualifyColumn('company_id'), $companyId);
    }
}
