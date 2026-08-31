<?php

namespace App\Http\Controllers;

use App\Http\Requests\GisFairTrackingLinkRequest;
use App\Models\GisFairCampaign;
use App\Models\GisFairTrackingLink;
use Illuminate\Http\Request;

class GisFairTrackingLinkController extends Controller
{
    public function store(GisFairTrackingLinkRequest $request, GisFairCampaign $campaign)
    {
        $campaign->trackingLinks()->create(array_merge($request->validated(), [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->back()->with('status', 'Tracking URL created.');
    }

    public function update(GisFairTrackingLinkRequest $request, GisFairCampaign $campaign, GisFairTrackingLink $link)
    {
        abort_unless($link->campaign_id === $campaign->id, 404);
        $link->update(array_merge($request->validated(), ['updated_by' => $request->user()->id]));

        return redirect()->back()->with('status', 'Tracking URL updated.');
    }

    public function destroy(Request $request, GisFairCampaign $campaign, GisFairTrackingLink $link)
    {
        abort_unless($request->user()->hasCrmPermission('funnel.config.manage'), 403);
        abort_unless($link->campaign_id === $campaign->id, 404);
        $link->delete();

        return redirect()->back()->with('status', 'Tracking URL removed.');
    }
}
