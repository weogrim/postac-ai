<?php

declare(strict_types=1);

namespace App\Auth\Requests;

use App\User\Models\UserModel;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currentUser = $this->user();
        $currentId = $currentUser?->getKey();
        $isGuest = $currentUser instanceof UserModel && $currentUser->isGuest();

        $emailRules = ['required', 'string', 'email', 'max:255'];

        // Ghost upgrade: kontroler ręcznie obsługuje konflikt emailu
        // (kasuje ghosta + loguje istniejące konto).
        if (! $isGuest) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($currentId);
        }

        return [
            'name' => ['required', 'string', 'max:50', Rule::unique('users', 'name')->ignore($currentId)],
            'email' => $emailRules,
            'password' => ['required', 'confirmed', Password::defaults()],
            'birthdate' => ['required', 'date', 'before:'.now()->subYears(13)->toDateString()],
            'accepted_terms' => ['accepted'],
            'accepted_privacy' => ['accepted'],
            'accepted_parental' => [Rule::when(fn (): bool => $this->requiresParentalConsent(), ['accepted'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nazwa',
            'email' => 'email',
            'password' => 'hasło',
            'birthdate' => 'data urodzenia',
            'accepted_terms' => 'zgoda na regulamin',
            'accepted_privacy' => 'zgoda na politykę prywatności',
            'accepted_parental' => 'zgoda rodzica',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birthdate.before' => 'Musisz mieć ukończone 13 lat.',
            'accepted_terms.accepted' => 'Musisz zaakceptować regulamin.',
            'accepted_privacy.accepted' => 'Musisz zaakceptować politykę prywatności.',
            'accepted_parental.accepted' => 'Wymagana zgoda rodzica lub opiekuna dla osób w wieku 13–15 lat.',
        ];
    }

    public function requiresParentalConsent(): bool
    {
        $birthdate = $this->input('birthdate');

        if (! is_string($birthdate) || $birthdate === '') {
            return false;
        }

        try {
            $age = (int) Carbon::parse($birthdate)->diffInYears(now());
        } catch (InvalidFormatException) {
            return false;
        }

        return $age >= 13 && $age < 16;
    }
}
