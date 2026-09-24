<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        $target = $this->route('role');
        $targetId = $target instanceof Role ? $target->getKey() : null;

        $slugRule = Rule::unique('roles', 'slug');

        if ($targetId !== null) {
            $slugRule->ignore($targetId);
        }

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'alpha_dash', 'max:100', $slugRule],
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
            'slug.alpha_dash' => 'شناسه فقط می‌تواند حروف لاتین، عدد، خط تیره و آندرلاین باشد.',
            'slug.unique' => 'این شناسه قبلاً ثبت شده است.',
            'permissions.*.exists' => 'یکی از مجوزهای انتخاب‌شده معتبر نیست.',
        ];
    }
}
