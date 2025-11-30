<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'rating'      => $this->rating,
            'comment'     => $this->comment,
            'is_published' => $this->is_published,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id'        => $this->user->id,
                    'full_name' => trim($this->user->first_name . ' ' . $this->user->last_name),
                    'avatar' => $this->user->avatar,
                    'city' => $this->user->city,
                    'state' => $this->user->state,
                ];
            }),
            'product'     => $this->whenLoaded('product', function () {
                return [
                    'id'        => $this->product->id,
                    'slug'        => $this->product->slug,
                    'title' => $this->product->title,
                    'image' => $this->product->image,
                ];
            }),
            'created_at'  => $this->created_at,
        ];
    }
}
