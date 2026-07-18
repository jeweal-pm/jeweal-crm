<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnquiryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'business_type' => $this->business_type,
            'email' => $this->email,
            'country' => $this->country,
            'phone' => $this->phone,
            'company' => $this->company,
            'company_website' => $this->company_website,
            'description' => $this->description,
            'interest_in' => $this->interest_in,
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
