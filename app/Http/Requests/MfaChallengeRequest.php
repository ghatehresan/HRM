<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\PersianNumbers;
use Illuminate\Foundation\Http\FormRequest;

class MfaChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    protected function prepareForValidation(): void
    {
        // Users may type Persian digits; TOTP/recovery verification is Latin-only.
        $this->merge(['code' => PersianNumbers::toEn((string) $this->input('code', ''))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'کد تأیید را وارد کنید.',
        ];
    }
}
