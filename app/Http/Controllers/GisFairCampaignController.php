<?php

namespace App\Http\Controllers;

use App\Http\Requests\GisFairCampaignRequest;
use App\Models\GisFairCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GisFairCampaignController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasCrmPermission('funnel.config.manage'), 403);

        return view('administrator.gis-fair.campaigns.index', [
            'campaigns' => GisFairCampaign::query()
                ->withCount(['leads', 'trackingLinks'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasCrmPermission('funnel.config.manage'), 403);

        return view('administrator.gis-fair.campaigns.form', [
            'campaign' => new GisFairCampaign([
                'status' => 'draft',
                'timezone' => 'Asia/Bangkok',
                'code_prefix' => 'GIS',
                'privacy_notice_version' => now()->format('Y-m-d'),
                'accepting_submissions' => true,
            ]),
        ]);
    }

    public function store(GisFairCampaignRequest $request)
    {
        $campaign = DB::transaction(function () use ($request) {
            $data = $request->validated();
            if ($data['status'] === 'active') {
                GisFairCampaign::query()->where('status', 'active')->update(['status' => 'closed']);
            }

            return GisFairCampaign::create(array_merge($data, [
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]));
        });

        return redirect()->route('gis-fair.campaigns.show', $campaign)->with('status', 'Fair event created.');
    }

    public function show(Request $request, GisFairCampaign $campaign)
    {
        abort_unless($request->user()->hasCrmPermission('funnel.config.manage'), 403);

        return view('administrator.gis-fair.campaigns.show', [
            'campaign' => $campaign->load(['trackingLinks' => fn ($query) => $query->latest('id')])->loadCount('leads'),
        ]);
    }

    public function edit(Request $request, GisFairCampaign $campaign)
    {
        abort_unless($request->user()->hasCrmPermission('funnel.config.manage'), 403);

        return view('administrator.gis-fair.campaigns.form', compact('campaign'));
    }

    public function update(GisFairCampaignRequest $request, GisFairCampaign $campaign)
    {
        DB::transaction(function () use ($request, $campaign) {
            $data = array_merge($request->validated(), ['updated_by' => $request->user()->id]);
            if ($data['status'] === 'active') {
                GisFairCampaign::query()->where('id', '<>', $campaign->id)->where('status', 'active')->update(['status' => 'closed']);
            }
            $campaign->update($data);
        });

        return redirect()->route('gis-fair.campaigns.show', $campaign)->with('status', 'Fair event configuration saved.');
    }

    public function destroy(Request $request, GisFairCampaign $campaign)
    {
        abort_unless($request->user()->hasCrmPermission('funnel.config.manage'), 403);
        abort_if($campaign->leads()->exists(), 422, 'An event with leads cannot be deleted. Close it instead.');
        $campaign->delete();

        return redirect()->route('gis-fair.campaigns.index')->with('status', 'Fair event deleted.');
    }
}
