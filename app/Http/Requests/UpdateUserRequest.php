<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $target = $this->route('user');
        $targetId = $target instanceof User ? $target->getKey() : null;

        $emailRule = Rule::unique('users', 'email');

        if ($targetId !== null) {
            $emailRule->ignore($targetId);
        }

        $email = $this->input('email', $target instanceof User ? $target->email : null);

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'string', 'email:filter', 'max:150', $emailRule],
            'password' => ['nullable', 'string', new StrongPassword(is_string($email) ? $email : null)],
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
            'email.email' => 'ایمیل معتبر نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'roles.*.exists' => 'یکی از نقش‌های انتخاب‌شده معتبر نیست.',
        ];
    }
}
