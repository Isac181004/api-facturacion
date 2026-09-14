<?php

namespace App\Http\Middleware;

use App\Models\IntegrationClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIntegration
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $plainKey = trim((string) $request->header('X-API-Key'));

        if ($plainKey === '') {
            return response()->json(['success' => false, 'message' => 'Falta la cabecera X-API-Key.'], 401);
        }

        $client = IntegrationClient::with('company')
            ->where('key_hash', hash('sha256', $plainKey))
            ->first();

        if (!$client || !$client->active || !$client->company?->activo) {
            return response()->json(['success' => false, 'message' => 'Clave API inválida o inactiva.'], 401);
        }

        if ($client->expires_at?->isPast()) {
            return response()->json(['success' => false, 'message' => 'La clave API ha vencido.'], 401);
        }

        if ($client->allowed_ips && !in_array($request->ip(), $client->allowed_ips, true)) {
            return response()->json(['success' => false, 'message' => 'IP no autorizada.'], 403);
        }

        if ($ability && !$client->allows($ability)) {
            return response()->json(['success' => false, 'message' => 'La integración no tiene este permiso.'], 403);
        }

        $rateKey = 'integration:'.$client->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($rateKey, $client->rate_limit_per_minute)) {
            return response()->json(['success' => false, 'message' => 'Límite de solicitudes excedido.'], 429);
        }
        RateLimiter::hit($rateKey, 60);

        $client->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('integration_client', $client);
        $request->attributes->set('integration_company', $client->company);
        $request->merge(['company_id' => $client->company_id]);

        return $next($request);
    }
}
