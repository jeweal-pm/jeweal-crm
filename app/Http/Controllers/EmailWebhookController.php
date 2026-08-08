<?php

namespace App\Http\Controllers;

use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\EmailSubscriber;
use App\Models\EmailSuppressionList;
use Illuminate\Http\Request;

class EmailWebhookController extends Controller
{
    public function brevo(Request $request)
    {
        $secret = config('email_management.brevo.webhook_secret');
        if ($secret && ! hash_equals($secret, (string) $request->header('X-Brevo-Signature'))) {
            abort(403);
        }

        $payload = $request->all();
        $isList = $payload === [] || array_keys($payload) === range(0, count($payload) - 1);
        $events = $isList ? $payload : [$payload];
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }

            $message = EmailMessage::query()
                ->where('message_id', $event['messageId'] ?? '')
                ->orWhere('provider_message_id', $event['messageId'] ?? '')
                ->first();
            if (! $message) {
                continue;
            }

            $type = strtolower((string) ($event['event'] ?? ''));
            $status = match ($type) {
                'delivered' => 'delivered', 'soft_bounce' => 'soft_bounce', 'hard_bounce' => 'hard_bounce',
                'blocked', 'invalid' => 'rejected', 'complaint' => 'complained', 'deferred' => 'deferred',
                default => null,
            };
            if ($status) {
                $message->update(['status' => $status, 'last_event_at' => now()]);
            }
            EmailEvent::create([
                'email_message_id' => $message->id,
                'event_type' => $type ?: 'provider_event',
                'metadata' => ['provider' => 'brevo'],
                'occurred_at' => now(),
            ]);

            if (in_array($status, ['hard_bounce', 'complained'], true)) {
                EmailSubscriber::query()->whereKey($message->email_subscriber_id)->update(['subscription_status' => $status]);
                EmailSuppressionList::updateOrCreate(
                    ['email' => $message->to_email, 'category' => 'all_marketing'],
                    ['reason' => $status, 'source' => 'brevo_webhook', 'suppressed_at' => now()]
                );
            }
        }

        return response()->json(['status' => 'accepted']);
    }
}
