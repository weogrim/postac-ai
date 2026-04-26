<?php

declare(strict_types=1);

namespace App\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordUpdateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hasPassword = $this->user()?->password !== null;

        return [
            'current_password' => [$hasPassword ? 'required' : 'nullable', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'obecne hasło',
            'password' => 'nowe hasło',
        ];
    }
}
