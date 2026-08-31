<?php

namespace App\Services\Whatsapp;

use App\Exceptions\TwilioDeliveryException;
use App\Models\TwilioConfiguration;
use App\Models\WhatsappMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TwilioWhatsappClient
{
    public function send(WhatsappMessage $message, TwilioConfiguration $config): array
    {
        if (! $config->is_enabled || ! $config->isComplete()) {
            throw new TwilioDeliveryException('Twilio WhatsApp is disabled or incomplete.', 'configuration');
        }

        $from = str_starts_with($config->whatsapp_from, 'whatsapp:')
            ? $config->whatsapp_from
            : 'whatsapp:'.$config->whatsapp_from;

        try {
            $response = Http::asForm()
                ->withBasicAuth($config->api_key_sid, $config->api_key_secret)
                ->acceptJson()
                ->timeout(15)
                ->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$config->account_sid}/Messages.json",
                    [
                        'From' => $from,
                        'To' => 'whatsapp:'.$message->recipient_normalized,
                        'Body' => $message->body,
                    ]
                );
        } catch (ConnectionException $exception) {
            throw new TwilioDeliveryException('Could not connect to Twilio.', 'connection');
        }

        $payload = $response->json();
        if (! $response->successful()) {
            throw new TwilioDeliveryException(
                (string) ($payload['message'] ?? 'Twilio rejected the WhatsApp message.'),
                isset($payload['code']) ? (string) $payload['code'] : (string) $response->status()
            );
        }

        return [
            'sid' => $payload['sid'] ?? null,
            'status' => $payload['status'] ?? 'queued',
        ];
    }
}
