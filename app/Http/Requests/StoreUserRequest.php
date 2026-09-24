<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasPermission('users.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $email = $this->input('email');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:filter', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', new StrongPassword(is_string($email) ? $email : null)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,slug'],
            'is_active' => ['sometimes', 'boolean'],
            'mfa_enforced' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام را وارد کنید.',
            'email.required' => 'ایمیل را وارد کنید.',
            'email.email' => 'ایمیل معتبر نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'roles.*.exists' => 'یکی از نقش‌های انتخاب‌شده معتبر نیست.',
        ];
    }
}
