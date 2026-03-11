<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePrescriptionRequest extends FormRequest
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
            'subtype' => ['sometimes', 'string', Rule::in(array_column(PrescriptionSubType::cases(), 'value'))],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*' => ['required', 'array'],
            'items.*.medication_name' => ['sometimes', 'string', 'max:255'],
            'items.*.dosage' => ['nullable', 'string', 'max:255'],
            'items.*.frequency' => ['nullable', 'string', 'max:255'],
            'items.*.duration' => ['nullable', 'string', 'max:255'],
            'items.*.medication_id' => ['nullable', 'integer'],
            'items.*.instructions' => ['nullable', 'string', 'max:1000'],
            'items.*.is_controlled' => ['nullable', 'boolean'],
            'items.*.control_type' => ['nullable', 'string', 'max:10'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.components' => ['nullable', 'array'],
            'items.*.posology' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
            'recipe_type_override' => ['nullable', 'boolean'],
            'recipe_type' => [
                Rule::requiredIf(fn () => (bool) $this->input('recipe_type_override')),
                'nullable',
                Rule::in(array_column(RecipeType::cases(), 'value')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subtype.in' => 'O subtipo informado é inválido.',
            'items.array' => 'O campo itens deve ser uma lista.',
            'items.min' => 'A prescrição deve conter pelo menos um item.',
            'items.*.array' => 'Cada item da prescrição deve ser um objeto válido.',
            'items.*.medication_name.max' => 'O nome do medicamento não pode ter mais de 255 caracteres.',
            'items.*.dosage.max' => 'A dosagem não pode ter mais de 255 caracteres.',
            'items.*.frequency.max' => 'A frequência não pode ter mais de 255 caracteres.',
            'items.*.duration.max' => 'A duração não pode ter mais de 255 caracteres.',
            'recipe_type.required_if' => 'O tipo de receita é obrigatório quando o override está ativo.',
            'recipe_type.in' => 'O tipo de receita informado é inválido.',
        ];
    }
}
