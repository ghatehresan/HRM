<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $email = $user instanceof User ? $user->email : null;

        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword($email)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'رمز عبور فعلی را وارد کنید.',
            'current_password.current_password' => 'رمز عبور فعلی اشتباه است.',
            'password.required' => 'رمز جدید را وارد کنید.',
            'password.confirmed' => 'تکرار رمز جدید مطابقت ندارد.',
        ];
    }
}
