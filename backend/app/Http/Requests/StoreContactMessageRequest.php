<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9() .-]{7,30}$/'],
            'message' => ['required', 'string', 'max:3000'],
            'consent' => ['required', 'accepted'],
        ];
    }
}
