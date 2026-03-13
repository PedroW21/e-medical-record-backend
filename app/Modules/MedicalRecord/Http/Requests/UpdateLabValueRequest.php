<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLabValueRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'value' => ['sometimes', 'string', 'max:255'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'reference_range' => ['nullable', 'string', 'max:255'],
            'collection_date' => ['sometimes', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.string' => 'O valor do resultado deve ser um texto.',
            'unit.string' => 'A unidade de medida deve ser um texto.',
            'collection_date.date' => 'A data de coleta deve ser uma data válida.',
            'collection_date.before_or_equal' => 'A data de coleta não pode ser futura.',
        ];
    }
}
