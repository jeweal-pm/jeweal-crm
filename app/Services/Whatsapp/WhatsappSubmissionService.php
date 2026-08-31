<?php

namespace App\Services\Whatsapp;

use App\Exceptions\DuplicateWhatsappRecipientException;
use App\Models\WhatsappMessage;
use App\Services\Security\IpAccessService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class WhatsappSubmissionService
{
    public function __construct(
        private WhatsappPhoneNormalizer $phoneNormalizer,
        private WhatsappDeliveryService $deliveryService
    ) {
    }

    public function submit(array $data, string $sourceIp): WhatsappMessage
    {
        $recipient = $this->phoneNormalizer->normalize($data['phone_number']);
        $config = $this->deliveryService->configuration();

        try {
            $message = WhatsappMessage::create([
                'public_id' => (string) Str::uuid(),
                'recipient' => $data['phone_number'],
                'recipient_normalized' => $recipient,
                'body' => $data['message'],
                'source_module' => 'whatsapp',
                'source_reference' => $data['reference_id'] ?? null,
                'status' => WhatsappMessage::STATUS_WAITING,
                'max_attempts' => $config->max_retry_attempts,
                'source_ip' => $sourceIp,
                'source_ip_hash' => IpAccessService::hash($sourceIp),
            ]);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw new DuplicateWhatsappRecipientException('A message for this WhatsApp number already exists.');
            }

            throw $exception;
        }

        if ($this->deliveryService->dailyLimitReached($config)) {
            return $this->deliveryService->queueForDailyLimit($message, $config);
        }

        return $this->deliveryService->attempt($message, $config);
    }
}
