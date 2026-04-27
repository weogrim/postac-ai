<?php

declare(strict_types=1);

namespace App\Dating\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DatingOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->isGuest();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'accepted_dating_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accepted_dating_terms.accepted' => 'Musisz zaakceptować regulamin sekcji Randki.',
        ];
    }
}
