<?php

namespace App\Http\Controllers;

use App\Models\GisFairTrackingLink;
use App\Services\GisFair\GisFairAttributionService;
use Illuminate\Http\Request;

class GisFairRedirectController extends Controller
{
    public function __invoke(Request $request, string $code, GisFairAttributionService $attribution)
    {
        $link = GisFairTrackingLink::query()
            ->with('campaign')
            ->where('code', $code)
            ->firstOrFail();

        if (! $link->isAvailable() || ! $link->campaign->isAvailableForTrackingRedirect()) {
            return redirect()->away($link->expiredRedirectUrl(), 302, $this->redirectHeaders());
        }

        $visit = $attribution->recordVisit($link, $request);

        return redirect()->away($visit->getAttribute('redirect_url'), 302, $this->redirectHeaders());
    }

    private function redirectHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
    }
}
