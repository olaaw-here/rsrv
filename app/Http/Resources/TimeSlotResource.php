<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'slot_date'  => $this->slot_date->format('Y-m-d'),
            'start_time' => substr($this->start_time, 0, 5),
            'end_time'   => substr($this->end_time, 0, 5),
            'price'      => (float) $this->price,
            'status'     => $this->status,
        ];
    }
}
