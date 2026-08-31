<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GisFairLeadResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'fairCode' => $this->fair_code,
            'eventCode' => $this->campaign?->code,
            'eventName' => $this->campaign?->name,
            'status' => $this->status,
        ];
    }
}
