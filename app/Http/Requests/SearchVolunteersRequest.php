<?php

namespace App\Http\Requests;

use App\Enums\SupportMode;
use App\Models\SupportRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchVolunteersRequest extends FormRequest
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
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'between:0,6'],
            'modes' => ['nullable', 'array'],
            'modes.*' => [Rule::enum(SupportMode::class)],
            'support_request' => ['nullable', 'integer', Rule::exists('support_requests', 'id')],
            'applied' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Normalized filter values consumed by the volunteer search service.
     *
     * @return array{days: array<int, int>, modes: array<int, string>, category_id?: int}
     */
    public function filters(): array
    {
        return [
            'days' => array_map('intval', $this->validated('days', [])),
            'modes' => array_values($this->validated('modes', [])),
        ];
    }

    /**
     * The support request that led the student here, if any. Drives the
     * backend skill/category filter and the mode pre-fill.
     */
    public function supportRequestContext(): ?SupportRequest
    {
        $id = (int) $this->validated('support_request');

        return $id > 0 ? SupportRequest::with('category')->find($id) : null;
    }

    /**
     * Whether the student explicitly submitted the filter form (as opposed
     * to arriving fresh from the "Find Volunteers" redirect). The hidden
     * `applied` marker lets "all boxes unchecked" differ from a fresh visit.
     */
    public function hasFilterInput(): bool
    {
        return $this->hasAny(['days', 'modes', 'applied']);
    }
}
