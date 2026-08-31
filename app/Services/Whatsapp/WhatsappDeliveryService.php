<?php

namespace App\Services\Whatsapp;

use App\Exceptions\TwilioDeliveryException;
use App\Models\TwilioConfiguration;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class WhatsappDeliveryService
{
    public function __construct(private TwilioWhatsappClient $client)
    {
    }

    public function configuration(): TwilioConfiguration
    {
        return TwilioConfiguration::query()->firstOrCreate(
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

    public function dailyLimitReached(TwilioConfiguration $config): bool
    {
        return $this->dailyUsageCount($config) >= $config->daily_limit;
    }

    public function queueForDailyLimit(WhatsappMessage $message, TwilioConfiguration $config): WhatsappMessage
    {
        $timezone = $config->timezone ?: config('app.timezone');
        $nextAttempt = Carbon::now($timezone)
            ->addDay()
            ->startOfDay()
            ->addMinute()
            ->setTimezone(config('app.timezone'));

        $message->update([
            'status' => WhatsappMessage::STATUS_WAITING,
            'wait_reason' => WhatsappMessage::WAIT_DAILY_LIMIT,
            'next_attempt_at' => $nextAttempt,
        ]);

        return $message->refresh();
    }

    public function attempt(WhatsappMessage $message, ?TwilioConfiguration $config = null): WhatsappMessage
    {
        $config ??= $this->configuration();

        if (! $config->is_enabled || ! $config->isComplete()) {
            $message->update([
                'status' => WhatsappMessage::STATUS_WAITING,
                'wait_reason' => WhatsappMessage::WAIT_CONFIGURATION,
                'next_attempt_at' => now()->addMinutes(5),
                'provider_error_code' => 'configuration',
                'provider_error_message' => 'Twilio WhatsApp is disabled or incomplete.',
            ]);

            return $message->refresh();
        }

        if (! $this->reserveDailySlot($message, $config)) {
            return $this->queueForDailyLimit($message, $config);
        }

        try {
            $result = $this->client->send($message, $config);
            $message->update([
                'status' => WhatsappMessage::STATUS_SENT,
                'wait_reason' => null,
                'provider_message_sid' => $result['sid'],
                'provider_status' => $result['status'],
                'provider_error_code' => null,
                'provider_error_message' => null,
                'next_attempt_at' => null,
                'sent_at' => now(),
                'failed_at' => null,
            ]);
        } catch (Throwable $exception) {
            $attempts = $message->attempts + 1;
            $failed = $attempts >= $message->max_attempts;
            $providerCode = $exception instanceof TwilioDeliveryException
                ? $exception->providerCode()
                : 'unexpected';

            $message->update([
                'status' => $failed ? WhatsappMessage::STATUS_FAILED : WhatsappMessage::STATUS_WAITING,
                'wait_reason' => $failed ? null : WhatsappMessage::WAIT_PROVIDER_FAILURE,
                'attempts' => $attempts,
                'next_attempt_at' => $failed ? null : now()->addSeconds($this->retryDelay($config, $attempts)),
                'provider_error_code' => $providerCode,
                'provider_error_message' => mb_substr($exception->getMessage(), 0, 1000),
                'failed_at' => $failed ? now() : null,
            ]);
        }

        return $message->refresh();
    }

    private function retryDelay(TwilioConfiguration $config, int $attempt): int
    {
        $delays = $config->retry_delays_seconds ?: [60, 300, 900];
        $index = min(max($attempt - 1, 0), count($delays) - 1);

        return max((int) ($delays[$index] ?? 60), 1);
    }

    private function reserveDailySlot(WhatsappMessage $message, TwilioConfiguration $config): bool
    {
        return DB::transaction(function () use ($message, $config) {
            $lockedConfig = TwilioConfiguration::query()->whereKey($config->id)->lockForUpdate()->firstOrFail();

            if ($this->dailyUsageCount($lockedConfig) >= $lockedConfig->daily_limit) {
                return false;
            }

            $message->update(['status' => WhatsappMessage::STATUS_PROCESSING]);

            return true;
        }, 3);
    }

    private function dailyUsageCount(TwilioConfiguration $config): int
    {
        $timezone = $config->timezone ?: config('app.timezone');
        $startOfDay = Carbon::now($timezone)->startOfDay()->setTimezone(config('app.timezone'));

        return WhatsappMessage::query()
            ->where(function ($query) use ($startOfDay) {
                $query->where(function ($query) use ($startOfDay) {
                    $query->where('status', WhatsappMessage::STATUS_SENT)
                        ->where('sent_at', '>=', $startOfDay);
                })->orWhere(function ($query) use ($startOfDay) {
                    $query->where('status', WhatsappMessage::STATUS_PROCESSING)
                        ->where('updated_at', '>=', $startOfDay);
                });
            })
            ->count();
    }
}
