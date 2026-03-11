<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePrescriptionTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'subtype' => ['required', 'string', Rule::in(array_column(PrescriptionSubType::cases(), 'value'))],
            'items' => ['required', 'array', 'min:1'],
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
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O campo nome não pode ter mais de 255 caracteres.',
            'subtype.required' => 'O campo subtipo é obrigatório.',
            'subtype.in' => 'O subtipo informado é inválido.',
            'items.required' => 'O campo itens é obrigatório.',
            'items.array' => 'O campo itens deve ser uma lista.',
            'items.min' => 'O modelo deve conter pelo menos um item.',
            'tags.array' => 'O campo tags deve ser uma lista.',
            'tags.*.max' => 'Cada tag não pode ter mais de 50 caracteres.',
        ];
    }
}
