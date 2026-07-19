<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IpSubmissionRateLimit
{
    private const COOLDOWN_SECONDS = 10;
    private const HOURLY_MAX = 10;
    private const DAILY_MAX = 30;
    private const HOURLY_BLOCK_SECONDS = 3600;

    private const MSG_COOLDOWN = 'กรุณารอ 10 วินาที แล้วส่งอีกครั้ง';
    private const MSG_HOURLY_BLOCK = 'IP ของคุณมีการส่งเกิน rate limit 1 ชั่วโมง กรุณารออีก 1 ชั่วโมง แล้วส่งอีกครั้ง';
    private const MSG_PERMANENT_BLOCK = 'ip ของคุณถูกบล็อกจากระบบของเรา เนื่องจาก มีพฤติกรรมเป็น spam';

    public function handle(Request $request, Closure $next)
    {
        $ip = $this->clientIp($request);

        $blockedResponse = $this->blockedResponse($ip);
        if ($blockedResponse) {
            return $blockedResponse;
        }

        $attemptResponse = $this->recordAttempt($ip);
        if ($attemptResponse) {
            return $attemptResponse;
        }

        return $next($request);
    }

    private function clientIp(Request $request): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $value = $request->server($key);
            if (! empty($value)) {
                return trim(explode(',', $value)[0]);
            }
        }

        return '0.0.0.0';
    }

    private function blockedResponse(string $ip): ?JsonResponse
    {
        $data = $this->load($ip);
        $now = time();

        if (! empty($data['permanent_block'])) {
            return $this->reject(403, self::MSG_PERMANENT_BLOCK);
        }

        if (! empty($data['hourly_blocked_until']) && (int) $data['hourly_blocked_until'] > $now) {
            return $this->reject(429, self::MSG_HOURLY_BLOCK);
        }

        if (! empty($data['hourly_blocked_until']) && (int) $data['hourly_blocked_until'] <= $now) {
            $data['hourly_blocked_until'] = 0;
            $this->save($ip, $data);
        }

        return null;
    }

    private function recordAttempt(string $ip): ?JsonResponse
    {
        $data = $this->load($ip);
        $now = time();

        $lastAttempt = (int) ($data['last_attempt'] ?? 0);
        if ($lastAttempt > 0 && ($now - $lastAttempt) < self::COOLDOWN_SECONDS) {
            return $this->reject(429, self::MSG_COOLDOWN);
        }

        $data['attempts'] = $this->pruneAttempts($data['attempts'] ?? []);
        $data['attempts'][] = $now;
        $data['last_attempt'] = $now;

        $hourlyCount = count(array_filter(
            $data['attempts'],
            static fn ($timestamp) => (int) $timestamp > ($now - self::HOURLY_BLOCK_SECONDS)
        ));
        $dailyCount = count($data['attempts']);

        if ($dailyCount > self::DAILY_MAX) {
            $data['permanent_block'] = true;
            $this->save($ip, $data);

            return $this->reject(403, self::MSG_PERMANENT_BLOCK);
        }

        if ($hourlyCount > self::HOURLY_MAX) {
            $data['hourly_blocked_until'] = $now + self::HOURLY_BLOCK_SECONDS;
            $this->save($ip, $data);

            return $this->reject(429, self::MSG_HOURLY_BLOCK);
        }

        $this->save($ip, $data);

        return null;
    }

    private function load(string $ip): array
    {
        $defaults = [
            'attempts' => [],
            'last_attempt' => 0,
            'hourly_blocked_until' => 0,
            'permanent_block' => false,
        ];

        $file = $this->file($ip);
        if (! is_file($file)) {
            return $defaults;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data)) {
            return $defaults;
        }

        return array_merge($defaults, $data);
    }

    private function save(string $ip, array $data): void
    {
        $dir = $this->directory();
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return;
        }

        file_put_contents(
            $this->file($ip),
            json_encode($data, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function pruneAttempts(array $attempts, int $keepSeconds = 86400): array
    {
        $cutoff = time() - $keepSeconds;

        return array_values(array_filter(
            $attempts,
            static fn ($timestamp) => (int) $timestamp > $cutoff
        ));
    }

    private function directory(): string
    {
        return storage_path('app/rate_limit');
    }

    private function file(string $ip): string
    {
        return $this->directory().'/'.hash('sha256', $ip).'.json';
    }

    private function reject(int $code, string $message): JsonResponse
    {
        return response()->json(['error' => $message], $code);
    }
}
