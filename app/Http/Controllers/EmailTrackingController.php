<?php

namespace App\Http\Controllers;

use App\Models\EmailEvent;
use App\Models\EmailMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class EmailTrackingController extends Controller
{
    public function open(Request $request, string $messageId)
    {
        $this->record($request, $messageId, 'opened');

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function click(Request $request, string $messageId)
    {
        $url = $request->query('url');
        abort_unless(is_string($url) && filter_var($url, FILTER_VALIDATE_URL), 404);
        $this->record($request, $messageId, 'clicked', $url);

        return redirect()->away($url);
    }

    private function record(Request $request, string $messageId, string $event, ?string $url = null): void
    {
        $key = 'email-tracking:'.$request->ip();
        abort_unless(RateLimiter::attempt($key, 120, fn () => true, 60), 429);

        $message = EmailMessage::query()->with('subscriber')->where('message_id', $messageId)->first();
        if (! $message) {
            return;
        }

        EmailEvent::create([
            'email_message_id' => $message->id,
            'event_type' => $event,
            'url' => $url,
            'ip_hash' => hash('sha256', (string) $request->ip()),
            'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
            'occurred_at' => now(),
        ]);

        $message->update(['last_event_at' => now()]);
        if ($message->subscriber) {
            $message->subscriber->update([$event === 'opened' ? 'last_opened_at' : 'last_clicked_at' => now()]);
        }
    }
}
