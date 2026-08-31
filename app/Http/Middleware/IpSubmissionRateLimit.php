<?php

namespace App\Http\Middleware;

use App\Models\IpRateLimitConfig;
use App\Services\Security\IpAccessService;
use Closure;
use Illuminate\Http\Request;

class IpSubmissionRateLimit
{
    public function __construct(private IpAccessService $ipAccessService)
    {
    }

    public function handle(Request $request, Closure $next, ?string $module = null)
    {
        $module ??= $this->inferModule($request);
        $ip = $this->clientIp($request);
        $request->attributes->set('client_ip', $ip);

        $decision = $this->ipAccessService->inspect($ip, $module, $request->path());
        if (! $decision['allowed']) {
            return response()->json([
                'success' => false,
                'error' => $decision['message'],
                'code' => $decision['decision'],
            ], $decision['status']);
        }

        return $next($request);
    }

    private function clientIp(Request $request): string
    {
        $candidate = (string) $request->ip();

        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }

        return '0.0.0.0';
    }

    private function inferModule(Request $request): string
    {
        return match (true) {
            str_contains($request->path(), 'gis-enquiry') => IpRateLimitConfig::MODULE_GIS,
            str_contains($request->path(), 'gis-fair') => IpRateLimitConfig::MODULE_GIS_FAIR,
            str_contains($request->path(), 'gms-stone-enquiry') => IpRateLimitConfig::MODULE_GMS,
            str_contains($request->path(), 'whatsapp') => IpRateLimitConfig::MODULE_WHATSAPP,
            default => IpRateLimitConfig::MODULE_JEWEAL,
        };
    }
}
