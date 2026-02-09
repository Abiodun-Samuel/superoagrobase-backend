<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'reference' => $this->reference,
            'transaction_reference' => $this->transaction_reference,
            'amount' => $this->amount,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_color' => $this->getStatusColor(),
            'channel' => $this->channel,
            'channel_label' => $this->getChannelLabel(),
            'currency' => $this->currency,
            'formatted_amount' => $this->getFormattedAmount(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->toISOString(),
            'created_at_human' => $this->created_at->diffForHumans(),
        ];
    }
}
