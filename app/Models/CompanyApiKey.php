<?php

namespace App\Models;

use App\Traits\ScopesToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyApiKey extends Model
{
    use ScopesToCompany;
    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'name',
        'key_prefix',
        'token_hash',
        'environment',
        'active',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'requests_count',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
        'requests_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
