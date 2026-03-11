<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListMedicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'controlled' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'O campo busca não pode ter mais de 255 caracteres.',
            'controlled.boolean' => 'O campo controlado deve ser verdadeiro ou falso.',
            'per_page.integer' => 'O campo itens por página deve ser um número inteiro.',
            'per_page.min' => 'O campo itens por página deve ser no mínimo 1.',
            'per_page.max' => 'O campo itens por página deve ser no máximo 100.',
        ];
    }
}
