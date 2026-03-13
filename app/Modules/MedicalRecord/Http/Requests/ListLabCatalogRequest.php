<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\LabCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListLabCatalogRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::in(LabCategory::cases())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category.in' => 'A categoria informada é inválida.',
            'per_page.max' => 'O campo itens por página deve ser no máximo 100.',
        ];
    }
}
