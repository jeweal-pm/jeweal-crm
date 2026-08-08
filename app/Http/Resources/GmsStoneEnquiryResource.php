<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GmsStoneEnquiryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'country_code' => $this->country_code,
            'account_type' => $this->account_type,
            'status' => $this->status,
            'business_name' => $this->business_name,
            'company_name' => $this->company_name,
            'tax_id' => $this->tax_id,
            'mailing_name' => $this->mailing_name,
            'website' => $this->website,
            'office_type' => $this->office_type,
            'branch_code' => $this->branch_code,
            'address' => $this->address,
            'country' => $this->country,
            'city' => $this->city,
            'province' => $this->province,
            'postcode' => $this->postcode,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'is_seen' => $this->is_seen,
            'is_approved' => $this->is_approved,
            'privacy_policy_accepted' => $this->privacy_policy_accepted,
            'terms_conditions_accepted' => $this->terms_conditions_accepted,
            'assigned_to' => $this->assigned_to,
            'assigned_by' => $this->assigned_by,
            'assigned_at' => $this->assigned_at,
            'last_updated_by' => $this->last_updated_by,
            'closed_at' => $this->closed_at,
            'closed_by' => $this->closed_by,
            'closed_by_role' => $this->closed_by_role,
            'counts_for_sale_kpi' => $this->counts_for_sale_kpi,
            'deleted_at' => $this->deleted_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
