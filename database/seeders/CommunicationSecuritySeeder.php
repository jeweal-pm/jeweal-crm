<?php

namespace Database\Seeders;

use App\Models\IpRateLimitConfig;
use App\Models\TwilioConfiguration;
use Illuminate\Database\Seeder;

class CommunicationSecuritySeeder extends Seeder
{
    public function run()
    {
        foreach (IpRateLimitConfig::defaults() as $module => $label) {
            IpRateLimitConfig::query()->firstOrCreate(
                ['module' => $module],
                [
                    'label' => $label,
                    'is_enabled' => true,
                    'max_attempts' => 5,
                    'window_seconds' => 86400,
                    'cooldown_seconds' => 10,
                ]
            );
        }

        TwilioConfiguration::query()->firstOrCreate(
            ['provider' => 'twilio'],
            [
                'is_enabled' => false,
                'daily_limit' => 100,
                'max_retry_attempts' => 3,
                'retry_delays_seconds' => [60, 300, 900],
                'timezone' => 'Asia/Bangkok',
            ]
        );
    }
}
