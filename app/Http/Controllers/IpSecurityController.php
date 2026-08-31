<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIpBlacklistRequest;
use App\Http\Requests\UpdateIpRateLimitConfigRequest;
use App\Models\IpBlacklist;
use App\Models\IpRateLimitConfig;
use App\Models\IpRateLimitLog;
use App\Services\Security\IpAccessService;
use Illuminate\Http\Request;

class IpSecurityController extends Controller
{
    public function index(Request $request)
    {
        $logs = IpRateLimitLog::query()->latest('occurred_at');

        if ($module = $request->input('module')) {
            $logs->where('module', $module);
        }
        if ($decision = $request->input('decision')) {
            $logs->where('decision', $decision);
        }
        if ($ip = trim((string) $request->input('ip'))) {
            $logs->where('ip_address', 'like', "%{$ip}%");
        }

        return view('administrator.security.ip-controls', [
            'configs' => IpRateLimitConfig::query()->orderBy('id')->get(),
            'blacklists' => IpBlacklist::query()->with('creator')->latest()->paginate(15, ['*'], 'blacklists_page'),
            'logs' => $logs->paginate(25, ['*'], 'logs_page')->appends($request->query()),
            'filters' => $request->only(['module', 'decision', 'ip']),
            'modules' => IpRateLimitConfig::defaults(),
        ]);
    }

    public function storeBlacklist(StoreIpBlacklistRequest $request)
    {
        $data = $request->validated();

        IpBlacklist::query()->updateOrCreate(
            ['ip_hash' => IpAccessService::hash($data['ip_address'])],
            [
                'ip_address' => $data['ip_address'],
                'reason' => $data['reason'] ?? null,
                'is_active' => true,
                'blocked_until' => $data['blocked_until'] ?? null,
                'created_by' => $request->user()->id,
            ]
        );

        return back()->with('success', 'IP address added to the global blacklist.');
    }

    public function destroyBlacklist(int $id)
    {
        IpBlacklist::query()->findOrFail($id)->delete();

        return back()->with('success', 'IP address removed from the global blacklist.');
    }

    public function updateRateLimit(UpdateIpRateLimitConfigRequest $request, int $id)
    {
        IpRateLimitConfig::query()->findOrFail($id)->update($request->validated());

        return back()->with('success', 'Rate limit configuration updated.');
    }
}
