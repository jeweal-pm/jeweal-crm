<?php

namespace App\Services\Email;

use App\Models\EmailSubscriber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailSubscriberService
{
    public function syncFromEnquiry(Model $enquiry, string $type): array
    {
        $context = $this->context($enquiry, $type);
        $subscriber = EmailSubscriber::firstOrNew(['email' => strtolower($context['email'])]);

        $token = $subscriber->unsubscribe_token_hash ? null : Str::random(64);
        $subscriber->fill(array_merge($context, [
            'source_type' => $type,
            'source_id' => $enquiry->getKey(),
            'subscription_status' => $subscriber->exists ? $subscriber->subscription_status : 'pending_confirmation',
            'subscribed_at' => $subscriber->subscribed_at ?: now(),
            'consent_source' => $subscriber->consent_source ?: 'enquiry_form',
            'lawful_basis' => $subscriber->lawful_basis ?: 'consent',
            'unsubscribe_token_hash' => $subscriber->unsubscribe_token_hash ?: hash('sha256', $token),
        ]))->save();

        return [$subscriber, $token];
    }

    public function context(Model $enquiry, string $type): array
    {
        if ($type === 'gis') {
            return [
                'email' => $enquiry->email,
                'first_name' => $enquiry->first_name,
                'last_name' => $enquiry->last_name,
                'company_name' => null,
            ];
        }

        if ($type === 'gms') {
            $name = preg_split('/\s+/', trim((string) $enquiry->full_name), 2);

            return [
                'email' => $enquiry->email,
                'first_name' => $name[0] ?? null,
                'last_name' => $name[1] ?? null,
                'company_name' => $enquiry->company_name ?: $enquiry->business_name,
            ];
        }

        $name = preg_split('/\s+/', trim((string) $enquiry->name), 2);

        return [
            'email' => $enquiry->email,
            'first_name' => $name[0] ?? null,
            'last_name' => $name[1] ?? null,
            'company_name' => $enquiry->company,
        ];
    }

    public function tokenFor(EmailSubscriber $subscriber): string
    {
        return $subscriber->unsubscribe_token_hash;
    }
}
