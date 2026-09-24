<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasPermission('roles.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'alpha_dash', 'max:100', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام نقش را وارد کنید.',
            'slug.required' => 'شناسهٔ نقش را وارد کنید.',
            'slug.alpha_dash' => 'شناسه فقط می‌تواند حروف لاتین، عدد، خط تیره و آندرلاین باشد.',
            'slug.unique' => 'این شناسه قبلاً ثبت شده است.',
            'permissions.*.exists' => 'یکی از مجوزهای انتخاب‌شده معتبر نیست.',
        ];
    }
}
