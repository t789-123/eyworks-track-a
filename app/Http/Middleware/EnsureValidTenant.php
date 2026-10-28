<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! is_string($tenantId) || trim($tenantId) === '' || ! ctype_digit($tenantId)) {
            return response()->json([
                'message' => 'Missing or invalid tenant header.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $tenant = Tenant::query()->find((int) $tenantId);

        if (! $tenant) {
            return response()->json([
                'message' => 'Unknown tenant.',
            ], Response::HTTP_NOT_FOUND);
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
