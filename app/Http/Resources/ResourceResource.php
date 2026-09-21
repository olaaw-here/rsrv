<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'type'                  => $this->type,
            'description'           => $this->description,
            'capacity'              => $this->capacity,
            'slot_duration_minutes' => $this->slot_duration_minutes,
            'base_price'            => (float) $this->base_price,
            'status'                => $this->status,
            'category' => [
                'id'   => $this->category?->id,
                'name' => $this->category?->name,
            ],
            'provider' => [
                'id'            => $this->provider?->id,
                'business_name' => $this->provider?->business_name,
                'city'          => $this->provider?->city,
                'rating_avg'    => (float) ($this->provider?->rating_avg ?? 0),
            ],
            'images' => $this->whenLoaded('images', fn () => $this->images->pluck('url')),
            'operational_hours' => $this->whenLoaded('operationalHours'),
            'created_at' => $this->created_at,
        ];
    }
}
