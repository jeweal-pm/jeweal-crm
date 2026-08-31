<?php

namespace App\Services\Email;

use App\Models\EmailAutomationConfig;
use Illuminate\Database\Eloquent\Model;

class EnquiryEmailAutomationService
{
    public function __construct(
        private EmailSubscriberService $subscribers,
        private EmailMessageService $messages
    ) {
    }

    public function dispatchFor(Model $enquiry, string $type, bool $force = false, bool $includeInternal = true): array
    {
        $queued = ['customer' => null, 'internal' => []];
        $config = EmailAutomationConfig::query()->where('enquiry_type', $type)->first();
        if (! $config || ! filter_var($enquiry->email, FILTER_VALIDATE_EMAIL)) {
            return $queued;
        }

        [$subscriber] = $this->subscribers->syncFromEnquiry($enquiry, $type);
        $data = $this->data($enquiry, $type, $subscriber);

        if ($config->customer_enabled && $config->customer_template_id) {
            $template = $config->customer_template_id ? $config->customerTemplate : null;
            if ($template) {
                $key = 'enquiry:'.$type.':customer:'.$enquiry->getKey();
                if ($force) {
                    $key .= ':resend:'.now()->format('YmdHisv');
                }
                $queued['customer'] = $this->messages->queue($subscriber, $template, $data, 'transactional', [], $key, $config->customer_delay_seconds);
            }
        }

        if ($includeInternal && $config->internal_enabled && $config->internal_template_id) {
            $template = $config->internalTemplate;
            if ($template) {
                $internalRecipients = $this->emails($config->internal_to);
                if ($internalRecipients === []) {
                    $internalRecipients = $this->emails(config('email_management.internal_recipients'));
                }
                if (in_array($config->internal_assignment_mode, ['assigned', 'config_and_assigned'], true) && $enquiry->assignedTo?->email) {
                    $internalRecipients[] = $enquiry->assignedTo->email;
                }
                foreach (array_values(array_unique($internalRecipients)) as $index => $email) {
                    $queued['internal'][] = $this->messages->queue($subscriber, $template, $data, 'internal', [
                        'to' => $email,
                        'cc' => $this->emails($config->internal_cc),
                        'bcc' => $this->emails($config->internal_bcc),
                    ], 'enquiry:'.$type.':internal:'.$enquiry->getKey().':'.$index.($force ? ':resend:'.now()->format('YmdHisv') : ''));
                }
            }
        }

        return $queued;
    }

    private function data(Model $enquiry, string $type, $subscriber): array
    {
        $context = $this->subscribers->context($enquiry, $type);

        return array_merge($context, [
            'enquiry_number' => strtoupper($type).'-'.$enquiry->getKey(),
            'enquiry_type' => $type,
            'submitted_at' => optional($enquiry->created_at)->timezone(config('email_management.timezone'))->format('D M j Y'),
            'sales_owner_name' => optional($enquiry->assignedTo)->name ?: (in_array($type, ['gis', 'gis_fair'], true) ? 'GIS Manage Pro Team' : 'Our Team'),
            'unsubscribe_url' => url('/unsubscribe/'.$this->subscribers->tokenFor($subscriber)),
            'country' => $enquiry->country,
            'phone' => $enquiry->phone ?? $enquiry->phone_number ?? $enquiry->phone_e164,
            'inquiry' => $enquiry->inquiry ?? '',
            'message' => $enquiry->description ?? $enquiry->message ?? $enquiry->inquiry,
            'fair_code' => $enquiry->fair_code ?? '',
            'event_name' => $enquiry->campaign?->name ?? '',
            'event_code' => $enquiry->campaign?->code ?? '',
            'event_dates' => $enquiry->campaign?->dates_display ?? '',
            'event_hall' => $enquiry->campaign?->hall ?? '',
            'event_booth' => $enquiry->campaign?->booth ?? '',
            'company' => $enquiry->company ?? '',
            'business_type' => $enquiry->business_type ?? '',
            'stores' => $enquiry->stores ?? '',
            'current_system' => $enquiry->current_system ?? '',
            'interests' => is_array($enquiry->interests ?? null) ? implode(', ', $enquiry->interests) : ($enquiry->interests ?? ''),
        ]);
    }

    private function emails(?array $values): array
    {
        return array_values(array_filter($values ?: [], fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL)));
    }
}
