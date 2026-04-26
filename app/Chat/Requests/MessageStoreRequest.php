<?php

declare(strict_types=1);

namespace App\Chat\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:8000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Treść wiadomości jest wymagana.',
            'content.max' => 'Wiadomość może mieć maksymalnie 8000 znaków.',
        ];
    }
}
