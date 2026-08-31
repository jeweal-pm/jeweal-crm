<?php

namespace App\Services\GisFair;

use App\Models\GisFairCampaign;
use App\Models\GisFairLead;
use App\Models\GisFairTrackingVisit;
use App\Models\User;
use App\Services\Email\EnquiryEmailAutomationService;
use App\Services\Spam\EnquirySpamScorer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GisFairLeadService
{
    public function __construct(
        private EnquirySpamScorer $spamScorer,
        private EnquiryEmailAutomationService $emailAutomation
    ) {
    }

    public function submit(array $data, Request $request): array
    {
        [$campaign, $visit] = $this->resolveAttribution($data);
        if (! $campaign->isOpenForSubmissions()) {
            throw new HttpException(410, 'This event registration is closed.');
        }

        $normalizedPhone = $this->normalizePhone($data['phoneIso'], $data['phone']);

        try {
            [$lead, $created] = $this->persist($campaign, $visit, $data, $normalizedPhone, $request);
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw $exception;
            }

            [$lead, $created] = $this->persist($campaign, $visit, $data, $normalizedPhone, $request);
        }

        try {
            $queued = $this->emailAutomation->dispatchFor(
                $lead->load('assignedTo'),
                'gis_fair',
                ! $created,
                $created
            );
            if ($queued['customer']?->exists) {
                $lead->forceFill([
                    'confirmation_sent_at' => now(),
                    'confirmation_send_count' => $lead->confirmation_send_count + 1,
                ])->save();
            }
        } catch (\Throwable $exception) {
            Log::error('GIS fair confirmation could not be queued.', [
                'lead_id' => $lead->id,
                'exception' => get_class($exception),
            ]);
        }

        return [$lead->fresh(['campaign', 'trackingLink']), $created];
    }

    public function resendConfirmation(GisFairLead $lead, ?User $actor = null): void
    {
        $queued = $this->emailAutomation->dispatchFor($lead->load('assignedTo'), 'gis_fair', true, false);
        if (! $queued['customer']?->exists) {
            throw new HttpException(422, 'The fair confirmation email is not configured.');
        }

        $lead->forceFill([
            'confirmation_sent_at' => now(),
            'confirmation_send_count' => $lead->confirmation_send_count + 1,
        ])->save();
        $lead->recordActivity(
            'status_changed',
            $actor,
            ['activity' => 'fair_code_resent'],
            $lead->status,
            $lead->status
        );
    }

    private function persist(
        GisFairCampaign $campaign,
        ?GisFairTrackingVisit $visit,
        array $data,
        array $phone,
        Request $request
    ): array {
        return DB::transaction(function () use ($campaign, $visit, $data, $phone, $request) {
            $lead = GisFairLead::withTrashed()
                ->where('campaign_id', $campaign->id)
                ->where('email', $data['email'])
                ->lockForUpdate()
                ->first();
            $created = ! $lead;
            $now = now();
            $marketingConsent = (bool) ($data['consent'] ?? false);

            if (! $lead) {
                $lead = new GisFairLead([
                    'campaign_id' => $campaign->id,
                    'tracking_link_id' => $visit?->tracking_link_id,
                    'tracking_visit_token' => $visit?->token,
                    'fair_code' => $this->fairCode($campaign),
                    'submission_count' => 1,
                    'privacy_agreed_at' => $now,
                    'marketing_consent_at' => $marketingConsent ? $now : null,
                ]);
            } else {
                if ($lead->trashed()) {
                    $lead->restore();
                    $lead->deleted_by = null;
                }
                $lead->submission_count++;
                if (! $lead->tracking_link_id && $visit) {
                    $lead->tracking_link_id = $visit->tracking_link_id;
                    $lead->tracking_visit_token = $visit->token;
                }
                if ($marketingConsent && ! $lead->marketing_consent) {
                    $lead->marketing_consent_at = $now;
                    $lead->marketing_consent_withdrawn_at = null;
                }
                if (! $marketingConsent && $lead->marketing_consent) {
                    $lead->marketing_consent_withdrawn_at = $now;
                }
            }

            $lead->fill([
                'first_name' => $data['firstName'],
                'last_name' => $data['lastName'],
                'email' => $data['email'],
                'company' => $data['company'] ?? '',
                'business_type' => $data['businessType'],
                'stores' => $data['stores'],
                'country' => $data['country'],
                'phone_iso' => $data['phoneIso'],
                'phone_local' => $phone['local'],
                'phone_e164' => $phone['e164'],
                'phone_dial_code' => $phone['dial'],
                'current_system' => $data['currentSystem'],
                'interests' => $data['interests'],
                'source' => $data['source'],
                'marketing_consent' => $marketingConsent,
                'privacy_agreed' => true,
                'privacy_notice_version' => $campaign->privacy_notice_version,
                'consent_ip' => $request->attributes->get('client_ip', $request->ip()),
                'consent_user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                'last_submitted_at' => $now,
            ])->save();

            $lead->submissions()->create([
                'campaign_id' => $campaign->id,
                'tracking_link_id' => $visit?->tracking_link_id,
                'tracking_visit_token' => $visit?->token,
                'source' => $data['source'],
                'privacy_agreed' => true,
                'privacy_notice_version' => $campaign->privacy_notice_version,
                'marketing_consent' => $marketingConsent,
                'consent_ip' => $request->attributes->get('client_ip', $request->ip()),
                'consent_user_agent' => mb_substr((string) $request->userAgent(), 0, 2000),
                'submitted_at' => $now,
            ]);

            if ($visit && ! $visit->converted_at) {
                $visit->update(['lead_id' => $lead->id, 'converted_at' => $now]);
                $visit->trackingLink()->increment('lead_count');
            }

            $this->spamScorer->apply($lead, ! $created);
            $lead->recordActivity('created', null, [
                'activity' => $created ? 'created' : 'resubmitted',
                'campaign_id' => $campaign->id,
                'tracking_link_id' => $visit?->tracking_link_id,
            ]);

            return [$lead, $created];
        });
    }

    private function resolveAttribution(array $data): array
    {
        $visit = null;
        if (! empty($data['trackingToken'])) {
            $visit = GisFairTrackingVisit::query()->with(['campaign', 'trackingLink'])->where('token', $data['trackingToken'])->first();
            if (! $visit || ! $visit->campaign || ! $visit->trackingLink) {
                throw new HttpException(422, 'The event tracking token is invalid.');
            }
            if (! empty($data['eventCode']) && $visit->campaign->code !== $data['eventCode']) {
                throw new HttpException(422, 'The event code does not match the tracking link.');
            }
        }

        $campaign = $visit?->campaign;
        if (! $campaign && ! empty($data['eventCode'])) {
            $campaign = GisFairCampaign::query()->where('code', $data['eventCode'])->first();
        }
        $campaign ??= GisFairCampaign::query()->where('status', 'active')->latest('id')->first();

        if (! $campaign) {
            throw new HttpException(404, 'No active fair event is configured.');
        }
        if ($visit && ! $visit->trackingLink->isAvailable()) {
            throw new HttpException(410, 'This registration link is no longer active.');
        }

        return [$campaign, $visit];
    }

    private function normalizePhone(string $iso, string $raw): array
    {
        $country = config('gis_fair.countries.'.$iso);
        $local = preg_replace('/\D+/', '', $raw);
        $trunk = array_key_exists('trunk', $country) ? $country['trunk'] : '0';
        $nsn = $trunk !== '' && str_starts_with($local, $trunk)
            ? substr($local, strlen($trunk))
            : $local;

        return ['local' => $local, 'dial' => $country['dial'], 'e164' => '+'.$country['dial'].$nsn];
    }

    private function fairCode(GisFairCampaign $campaign): string
    {
        $prefix = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $campaign->code_prefix));

        do {
            $code = $prefix.'-'.Str::upper(Str::random(6));
        } while (GisFairLead::withTrashed()->where('fair_code', $code)->exists());

        return $code;
    }
}
