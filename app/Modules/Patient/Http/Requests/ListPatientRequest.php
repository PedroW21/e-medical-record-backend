<?php

declare(strict_types=1);

namespace App\Modules\Patient\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListPatientRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'O campo página deve ser um número inteiro.',
            'page.min' => 'O campo página deve ser no mínimo 1.',
            'per_page.integer' => 'O campo itens por página deve ser um número inteiro.',
            'per_page.min' => 'O campo itens por página deve ser no mínimo 1.',
            'per_page.max' => 'O campo itens por página deve ser no máximo 100.',
            'status.in' => 'O status informado é inválido.',
        ];
    }
}
