<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
        $email = $this->input('email');

        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:filter', 'max:150'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword(is_string($email) ? $email : null)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'ایمیل را وارد کنید.',
            'email.email' => 'ایمیل معتبر نیست.',
            'password.required' => 'رمز جدید را وارد کنید.',
            'password.confirmed' => 'تکرار رمز جدید مطابقت ندارد.',
        ];
    }
}
