<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGisFairLeadRequest;
use App\Http\Resources\GisFairLeadResource;
use App\Models\GisFairCampaign;
use App\Services\GisFair\GisFairLeadService;
use Illuminate\Http\Request;

class GisFairLeadController extends Controller
{
    public function store(StoreGisFairLeadRequest $request, GisFairLeadService $service)
    {
        if ($request->filled('website')) {
            return response()->json(['ok' => true, 'success' => true], 200);
        }

        [$lead, $created] = $service->submit($request->validated(), $request);

        return response()->json([
            'ok' => true,
            'success' => true,
            'status' => 'complete',
            'duplicate' => ! $created,
            'leadId' => $lead->id,
            'fairCode' => $lead->fair_code,
            'data' => new GisFairLeadResource($lead),
        ], $created ? 201 : 200);
    }

    public function config(Request $request)
    {
        $request->validate(['event' => ['nullable', 'string', 'max:64']]);

        $campaign = GisFairCampaign::query()
            ->when($request->query('event'), fn ($query, $code) => $query->where('code', $code))
            ->when(! $request->query('event'), fn ($query) => $query->where('status', 'active'))
            ->latest('id')
            ->firstOrFail();

        return response()->json([
            'eventCode' => $campaign->code,
            'eventName' => $campaign->name,
            'edition' => $campaign->edition,
            'hall' => $campaign->hall,
            'booth' => $campaign->booth,
            'dates' => $campaign->dates_display,
            'startsAt' => optional($campaign->starts_at)->toIso8601String(),
            'endsAt' => optional($campaign->ends_at)->toIso8601String(),
            'offerDeadline' => optional($campaign->offer_deadline)->toIso8601String(),
            'timezone' => $campaign->timezone,
            'privacyNoticeVersion' => $campaign->privacy_notice_version,
            'privacyNoticeUrl' => $campaign->privacy_notice_url,
            'contactEmail' => $campaign->contact_email,
            'acceptingSubmissions' => $campaign->isOpenForSubmissions(),
        ]);
    }
}
