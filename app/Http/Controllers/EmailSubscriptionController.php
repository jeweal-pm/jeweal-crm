<?php

namespace App\Http\Controllers;

use App\Models\EmailSubscriber;
use App\Models\EmailSuppressionList;
use Illuminate\Http\Request;

class EmailSubscriptionController extends Controller
{
    public function show(string $token)
    {
        return view('email.unsubscribe', ['subscriber' => $this->subscriber($token)]);
    }

    public function unsubscribe(Request $request, string $token)
    {
        $subscriber = $this->subscriber($token);
        $category = $request->input('category', 'all_marketing');
        abort_unless(in_array($category, ['all_marketing', 'promotion', 'newsletter'], true), 422);

        $subscriber->update([
            'subscription_status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
        EmailSuppressionList::updateOrCreate(
            ['email' => $subscriber->email, 'category' => $category],
            ['reason' => $request->input('reason'), 'source' => 'unsubscribe_link', 'suppressed_at' => now()]
        );

        return view('email.unsubscribe-success', compact('subscriber'));
    }

    private function subscriber(string $token): EmailSubscriber
    {
        return EmailSubscriber::query()->where('unsubscribe_token_hash', $token)->firstOrFail();
    }
}
