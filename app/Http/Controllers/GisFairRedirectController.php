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

        abort_unless($link->isAvailable(), 410, 'This event link is no longer active.');

        $visit = $attribution->recordVisit($link, $request);

        return redirect()->away($visit->getAttribute('redirect_url'), 302, [
            'Cache-Control' => 'no-store, private',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ]);
    }
}
