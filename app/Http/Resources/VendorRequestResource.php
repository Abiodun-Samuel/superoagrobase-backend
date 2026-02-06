<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'company_name' => $this->company_name,
            'company_email' => $this->company_email,
            'company_phone' => $this->company_phone,
            'company_address' => $this->company_address,
            'company_website' => $this->company_website,
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'rejection_reason' => $this->when($this->rejection_reason, $this->rejection_reason),

            // Review information
            'reviewed_by' => $this->when($this->reviewed_by, [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->first_name . ' ' . $this->reviewer?->last_name,
                'email' => $this->reviewer?->email,
            ]),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'reviewed_at_human' => $this->reviewed_at?->diffForHumans(),

            // Related user (if approved)
            'user' => $this->when($this->user_id, [
                'id' => $this->user?->id,
                'name' => $this->user?->first_name . ' ' . $this->user?->last_name,
                'email' => $this->user?->email,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Computed properties
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected(),
            'can_be_reviewed' => $this->isPending(),
        ];
    }
}
