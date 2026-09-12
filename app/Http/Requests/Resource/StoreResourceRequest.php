<?php

namespace App\Http\Requests\Resource;

use Illuminate\Foundation\Http\FormRequest;

class StoreResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->providerProfile !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:place,field,room,service,product'],
            'description' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'slot_duration_minutes' => ['required', 'integer', 'min:15'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['url'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The selected type is invalid. Allowed values are: place, field, room, service, product.',
            'slot_duration_minutes.min' => 'The slot duration must be at least 15 minutes.',
        ];
    }

    
}
