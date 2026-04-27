<?php

declare(strict_types=1);

namespace App\Reporting\Requests;

use App\Reporting\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(['message', 'character'])],
            'reportable_id' => ['required', 'string', 'max:64'],
            'reason' => ['required', 'string', Rule::enum(ReportReason::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reportable_type' => 'typ treści',
            'reportable_id' => 'identyfikator treści',
            'reason' => 'powód',
            'description' => 'opis',
        ];
    }
}
