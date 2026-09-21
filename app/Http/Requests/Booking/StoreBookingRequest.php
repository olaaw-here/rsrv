<?php

namespace App\Http\Requests\Booking;

use App\Models\TimeSlot;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resource_id' => ['required', 'integer', 'exists:resources,id'],
            'time_slot_ids' => ['required', 'array', 'min:1', 'exists:time_slots,id'],
            'time_slot_ids.*' => ['integer', 'distinct', 'exists:time_slots,id'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'time_slot_ids.required' => 'At least one time slot must be selected.',
            'time_slot_ids.min' => 'At least one time slot must be selected.',
            'time_slot_ids.exists' => 'One or more selected time slots are invalid.',
            'time_slot_ids.*.distinct' => 'Duplicate time slots are not allowed.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $resourceId = $this->input('resource_id');
            $slotIds = $this->input('time_slot_ids', []);
            if (! $resourceId || empty($slotIds)) {
                return;
            }

            $validCount = TimeSlot::whereIn('id', $slotIds)
                ->where('resource_id', $resourceId)
                ->count();

            if ($validCount !== count($slotIds)) {
                $validator->errors()->add('time_slot_ids', 'One or more selected time slots do not belong to the specified resource.');
            }
        });
    }
}
