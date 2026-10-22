<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreValuationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'website' => ['nullable', 'string', 'max:0'],
            'idempotency_key' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[+0-9() .-]{7,30}$/'],
            'treatment_id' => ['required', 'integer', Rule::exists('treatments', 'id')->where('status', 'published')],
            'preferred_date' => ['nullable', 'required_with:preferred_time', 'date_format:Y-m-d', 'after_or_equal:today'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
            'consent' => ['required', 'accepted'],
        ];
    }
}
