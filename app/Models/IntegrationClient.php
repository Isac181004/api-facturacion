<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationClient extends Model
{
    protected $fillable = [
        'company_id', 'name', 'key_prefix', 'key_hash', 'abilities',
        'allowed_ips', 'rate_limit_per_minute', 'last_used_at',
        'expires_at', 'active',
    ];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'allowed_ips' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function allows(string $ability): bool
    {
        $abilities = $this->abilities ?? ['*'];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }
}
