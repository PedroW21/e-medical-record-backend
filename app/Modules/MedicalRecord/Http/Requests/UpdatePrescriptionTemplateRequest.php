<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'subtype' => ['sometimes', 'string', Rule::in(array_column(PrescriptionSubType::cases(), 'value'))],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
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
            'subtype.in' => 'O subtipo informado é inválido.',
            'items.array' => 'O campo itens deve ser uma lista.',
            'items.min' => 'O modelo deve conter pelo menos um item.',
            'items.*.array' => 'Cada item deve ser um objeto.',
            'tags.array' => 'O campo tags deve ser uma lista.',
            'tags.*.max' => 'Cada tag não pode ter mais de 50 caracteres.',
        ];
    }
}
