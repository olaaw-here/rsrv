<?php

namespace App\Http\Requests\Resource;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $resource = $this->route('resource');
        $providerProfile = $this->user()?->providerProfile;

        return $providerProfile && $providerProfile->id === $resource->provider_profile_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:place,field,room,service,product'],
            'description' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'slot_duration_minutes' => ['sometimes', 'required', 'integer', 'min:15'],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
