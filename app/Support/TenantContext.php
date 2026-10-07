<?php

namespace App\Support;

/** Request-scoped tenant and environment resolved only from trusted credentials. */
final class TenantContext
{
    private ?int $companyId = null;
    private ?string $environment = null;

    public function setCompany(int $companyId, ?string $environment = null): void
    {
        $this->companyId = $companyId;
        $this->environment = $environment;
    }

    public function clear(): void
    {
        $this->companyId = null;
        $this->environment = null;
    }

    public function companyId(): ?int
    {
        return $this->companyId;
    }

    public function isScoped(): bool
    {
        return $this->companyId !== null;
    }

    public function environmentFor(int $companyId): ?string
    {
        return $this->companyId === $companyId ? $this->environment : null;
    }
}
