<?php

namespace App\Http\Requests;

use App\Enums\SupportMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportRequestRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'support_mode' => ['required', Rule::enum(SupportMode::class)],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a subject category.',
            'category_id.exists' => 'The selected category is invalid.',
            'description.required' => 'Please describe what you need help with.',
            'description.min' => 'Please provide at least :min characters so volunteers can understand your problem.',
            'support_mode.required' => 'Please choose a preferred support mode.',
            'support_mode.Illuminate\Validation\Rules\Enum' => 'Please choose a valid support mode.',
        ];
    }
}
