<?php

namespace App\Console\Commands;

use App\Models\WhatsappMessage;
use App\Services\Whatsapp\WhatsappDeliveryService;
use Illuminate\Console\Command;

class ProcessWaitingWhatsappMessages extends Command
{
    protected $signature = 'whatsapp:process-waiting {--limit=100}';

    protected $description = 'Send due WhatsApp messages from the waiting list';

    public function handle(WhatsappDeliveryService $deliveryService): int
    {
        $limit = max((int) $this->option('limit'), 1);
        WhatsappMessage::query()
            ->where('status', WhatsappMessage::STATUS_PROCESSING)
            ->where('updated_at', '<=', now()->subMinutes(5))
            ->update([
                'status' => WhatsappMessage::STATUS_WAITING,
                'wait_reason' => WhatsappMessage::WAIT_PROVIDER_FAILURE,
                'next_attempt_at' => now(),
            ]);

        $messages = WhatsappMessage::query()->due()->oldest('id')->limit($limit)->get();
        $sent = 0;
        $waiting = 0;
        $failed = 0;

        foreach ($messages as $message) {
            $result = $deliveryService->attempt($message);

            match ($result->status) {
                WhatsappMessage::STATUS_SENT => $sent++,
                WhatsappMessage::STATUS_FAILED => $failed++,
                default => $waiting++,
            };
        }

        $this->info("Processed {$messages->count()} message(s): {$sent} sent, {$waiting} waiting, {$failed} failed.");

        return self::SUCCESS;
    }
}
