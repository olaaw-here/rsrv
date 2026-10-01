<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'business_name' => $this->business_name,
            'description'   => $this->description,
            'address'       => $this->address,
            'city'          => $this->city,
            'latitude'      => $this->latitude ? (float) $this->latitude : null,
            'longitude'     => $this->longitude ? (float) $this->longitude : null,
            'rating_avg'    => (float) $this->rating_avg,
            'total_reviews' => (int) $this->total_reviews,
            'status'        => $this->status,
            'user'          => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'resources_count' => $this->whenCounted('resources'),
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
