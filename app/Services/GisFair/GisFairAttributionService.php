<?php

namespace App\Services\GisFair;

use App\Models\GisFairTrackingLink;
use App\Models\GisFairTrackingVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GisFairAttributionService
{
    public function recordVisit(GisFairTrackingLink $link, Request $request): GisFairTrackingVisit
    {
        $destination = $link->destination_url ?: $link->campaign->landing_url;
        $token = (string) Str::uuid();
        $query = array_filter([
            'event' => $link->campaign->code,
            'ref' => $token,
            'utm_source' => $link->source,
            'utm_medium' => $link->medium,
            'utm_campaign' => $link->campaign->code,
            'utm_content' => $link->content,
        ], fn ($value) => $value !== null && $value !== '');

        $visit = GisFairTrackingVisit::create([
            'token' => $token,
            'campaign_id' => $link->campaign_id,
            'tracking_link_id' => $link->id,
            'ip_hash' => $this->hash($request->ip()),
            'user_agent_hash' => $this->hash($request->userAgent()),
            'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 2000),
            'destination_url' => $destination,
            'query_parameters' => Arr::only($request->query(), [
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            ]),
            'visited_at' => now(),
        ]);

        $link->increment('click_count');
        $visit->setAttribute('redirect_url', $this->appendQuery($destination, $query));

        return $visit;
    }

    private function appendQuery(string $url, array $query): string
    {
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        if ($fragment !== null) {
            $url = substr($url, 0, -(strlen($fragment) + 1));
        }

        $separator = str_contains($url, '?') ? '&' : '?';
        $result = $url.$separator.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $fragment !== null ? $result.'#'.$fragment : $result;
    }

    private function hash(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
