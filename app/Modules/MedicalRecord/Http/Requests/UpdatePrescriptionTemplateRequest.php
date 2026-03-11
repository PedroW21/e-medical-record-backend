<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePrescriptionTemplateRequest extends FormRequest
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
            'items' => ['sometimes', 'array', 'min:1'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'items.array' => 'O campo itens deve ser uma lista.',
            'items.min' => 'O modelo deve conter pelo menos um item.',
            'tags.array' => 'O campo tags deve ser uma lista.',
            'tags.*.max' => 'Cada tag não pode ter mais de 50 caracteres.',
        ];
    }
}
