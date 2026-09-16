<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GisEnquiryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'inquiry' => $this->inquiry,
            'message' => $this->message,
            'privacy_policy_accepted' => $this->privacy_policy_accepted,
            'marketing_consent' => $this->marketing_consent,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'assigned_at' => $this->assigned_at,
            'closed_at' => $this->closed_at,
            'deleted_at' => $this->deleted_at,
            'spam_status' => $this->spam_status,
            'spam_score' => $this->spam_score,
            'spam_reasons' => $this->spam_reasons,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
