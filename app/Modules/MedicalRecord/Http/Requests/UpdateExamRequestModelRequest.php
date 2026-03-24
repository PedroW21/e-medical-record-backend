<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateExamRequestModelRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['required_with:items', 'string', 'max:255'],
            'items.*.name' => ['required_with:items', 'string', 'max:500'],
            'items.*.tuss_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'category.max' => 'O campo categoria não pode ter mais de 100 caracteres.',
            'items.array' => 'O campo itens deve ser uma lista.',
            'items.min' => 'O modelo deve conter pelo menos um item.',
            'items.*.id.required_with' => 'Cada item deve ter um identificador.',
            'items.*.id.max' => 'O identificador do item não pode ter mais de 255 caracteres.',
            'items.*.name.required_with' => 'Cada item deve ter um nome.',
            'items.*.name.max' => 'O nome do item não pode ter mais de 500 caracteres.',
            'items.*.tuss_code.max' => 'O código TUSS não pode ter mais de 50 caracteres.',
        ];
    }
}
