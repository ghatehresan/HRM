<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:filter', 'max:150'],
            'password' => ['required', 'string'],
            // TOTP code or recovery code; required only when the account
            // is MFA-bound (checked in the controller, not here).
            'code' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
