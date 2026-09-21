<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'booking_code'   => $this->booking_code,
            'status'         => $this->status,
            'total_price'    => (float) $this->total_price,
            'customer_notes' => $this->customer_notes,
            'expires_at'     => $this->expires_at,
            'confirmed_at'   => $this->confirmed_at,
            'resource' => [
                'id'   => $this->resource?->id,
                'name' => $this->resource?->name,
                'type' => $this->resource?->type,
            ],
            'slots' => $this->whenLoaded('bookingSlots', fn () => $this->bookingSlots->map(fn ($bs) => [
                'time_slot_id'   => $bs->time_slot_id,
                'slot_date'      => $bs->timeSlot?->slot_date?->format('Y-m-d'),
                'start_time'     => substr($bs->timeSlot?->start_time ?? '', 0, 5),
                'end_time'       => substr($bs->timeSlot?->end_time ?? '', 0, 5),
                'price_snapshot' => (float) $bs->price_snapshot,
            ])),
            'payment' => $this->whenLoaded('payments', function () {
                $latest = $this->payments->sortByDesc('created_at')->first();

                return $latest ? [
                    'status'      => $latest->status,
                    'snap_token'  => $latest->snap_token,
                    'payment_url' => $latest->payment_url,
                    'paid_at'     => $latest->paid_at,
                ] : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}
