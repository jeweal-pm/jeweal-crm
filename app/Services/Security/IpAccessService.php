<?php

namespace App\Services\Security;

use App\Models\IpBlacklist;
use App\Models\IpRateLimitConfig;
use App\Models\IpRateLimitLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IpAccessService
{
    public function inspect(string $ip, string $module, ?string $endpoint = null): array
    {
        $ipHash = self::hash($ip);
        $now = now();

        $blacklisted = IpBlacklist::query()
            ->where('ip_hash', $ipHash)
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('blocked_until')->orWhere('blocked_until', '>', $now);
            })
            ->exists();

        if ($blacklisted) {
            return $this->reject(
                $ip,
                $module,
                $endpoint,
                IpRateLimitLog::DECISION_BLACKLISTED,
                403,
                'This IP address is blocked.'
            );
        }

        IpRateLimitConfig::query()->firstOrCreate(
            ['module' => $module],
            [
                'label' => Str::headline($module),
                'is_enabled' => true,
                'max_attempts' => 5,
                'window_seconds' => 86400,
                'cooldown_seconds' => 10,
            ]
        );

        return DB::transaction(function () use ($ip, $ipHash, $module, $endpoint) {
            // Locking the module policy makes counting and reserving an attempt atomic.
            $config = IpRateLimitConfig::query()
                ->where('module', $module)
                ->lockForUpdate()
                ->firstOrFail();

            if ($config->is_enabled && $config->cooldown_seconds > 0) {
                $lastAllowedAt = IpRateLimitLog::query()
                    ->where('module', $module)
                    ->where('ip_hash', $ipHash)
                    ->where('decision', IpRateLimitLog::DECISION_ALLOWED)
                    ->latest('occurred_at')
                    ->value('occurred_at');

                if ($lastAllowedAt && now()->diffInSeconds($lastAllowedAt) < $config->cooldown_seconds) {
                    return $this->reject(
                        $ip,
                        $module,
                        $endpoint,
                        IpRateLimitLog::DECISION_COOLDOWN,
                        429,
                        "กรุณารอ {$config->cooldown_seconds} วินาที แล้วส่งอีกครั้ง"
                    );
                }
            }

            if ($config->is_enabled) {
                $attempts = IpRateLimitLog::query()
                    ->where('module', $module)
                    ->where('ip_hash', $ipHash)
                    ->where('decision', IpRateLimitLog::DECISION_ALLOWED)
                    ->where('occurred_at', '>=', now()->subSeconds($config->window_seconds))
                    ->count();

                if ($attempts >= $config->max_attempts) {
                    return $this->reject(
                        $ip,
                        $module,
                        $endpoint,
                        IpRateLimitLog::DECISION_RATE_LIMITED,
                        429,
                        'This IP address has reached the submission limit. Please try again later.'
                    );
                }
            }

            $this->log($ip, $module, $endpoint, IpRateLimitLog::DECISION_ALLOWED, 200, [
                'limit_enabled' => $config->is_enabled,
                'max_attempts' => $config->max_attempts,
                'window_seconds' => $config->window_seconds,
            ]);

            return ['allowed' => true, 'status' => 200, 'decision' => IpRateLimitLog::DECISION_ALLOWED];
        }, 3);
    }

    public static function hash(string $ip): string
    {
        return hash('sha256', trim($ip));
    }

    private function reject(
        string $ip,
        string $module,
        ?string $endpoint,
        string $decision,
        int $status,
        string $message
    ): array {
        $this->log($ip, $module, $endpoint, $decision, $status);

        return compact('status', 'decision', 'message') + ['allowed' => false];
    }

    private function log(
        string $ip,
        string $module,
        ?string $endpoint,
        string $decision,
        int $status,
        array $metadata = []
    ): void {
        IpRateLimitLog::create([
            'request_id' => (string) Str::uuid(),
            'module' => $module,
            'ip_address' => $ip,
            'ip_hash' => self::hash($ip),
            'endpoint' => $endpoint,
            'decision' => $decision,
            'http_status' => $status,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }
}
