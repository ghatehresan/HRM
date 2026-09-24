<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\PersianNumbers;
use Illuminate\Foundation\Http\FormRequest;

class MfaSetupConfirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => PersianNumbers::toEn((string) $this->input('code', ''))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'کد ۶ رقمی نمایش‌داده‌شده در اپ را وارد کنید.',
            'code.size' => 'کد تأیید باید ۶ رقم باشد.',
        ];
    }
}
