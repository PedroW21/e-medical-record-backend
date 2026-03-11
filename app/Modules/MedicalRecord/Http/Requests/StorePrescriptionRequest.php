<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\PrescriptionSubType;
use App\Modules\MedicalRecord\Enums\RecipeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePrescriptionRequest extends FormRequest
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
        $allopathicTypes = [
            PrescriptionSubType::Allopathic->value,
            PrescriptionSubType::InjectableIm->value,
            PrescriptionSubType::InjectableEv->value,
            PrescriptionSubType::InjectableCombined->value,
            PrescriptionSubType::InjectableProtocol->value,
            PrescriptionSubType::Glp1->value,
            PrescriptionSubType::Steroid->value,
        ];

        return [
            'subtype' => ['required', 'string', Rule::in(array_column(PrescriptionSubType::cases(), 'value'))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_name' => [
                Rule::requiredIf(fn () => in_array($this->input('subtype'), $allopathicTypes, true)),
                'string',
                'max:255',
            ],
            'items.*.dosage' => [
                Rule::requiredIf(fn () => $this->input('subtype') === PrescriptionSubType::Allopathic->value),
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.frequency' => [
                Rule::requiredIf(fn () => $this->input('subtype') === PrescriptionSubType::Allopathic->value),
                'nullable',
                'string',
                'max:255',
            ],
            'items.*.duration' => [
                Rule::requiredIf(fn () => $this->input('subtype') === PrescriptionSubType::Allopathic->value),
                'nullable',
                'string',
                'max:255',
            ],
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
            'subtype.required' => 'O campo subtipo é obrigatório.',
            'subtype.in' => 'O subtipo informado é inválido.',
            'items.required' => 'O campo itens é obrigatório.',
            'items.array' => 'O campo itens deve ser uma lista.',
            'items.min' => 'A prescrição deve conter pelo menos um item.',
            'items.*.medication_name.required' => 'O nome do medicamento é obrigatório para o subtipo selecionado.',
            'items.*.medication_name.max' => 'O nome do medicamento não pode ter mais de 255 caracteres.',
            'items.*.dosage.required' => 'A dosagem é obrigatória para prescrições alopáticas.',
            'items.*.dosage.max' => 'A dosagem não pode ter mais de 255 caracteres.',
            'items.*.frequency.required' => 'A frequência é obrigatória para prescrições alopáticas.',
            'items.*.frequency.max' => 'A frequência não pode ter mais de 255 caracteres.',
            'items.*.duration.required' => 'A duração é obrigatória para prescrições alopáticas.',
            'items.*.duration.max' => 'A duração não pode ter mais de 255 caracteres.',
            'recipe_type.required_if' => 'O tipo de receita é obrigatório quando o override está ativo.',
            'recipe_type.in' => 'O tipo de receita informado é inválido.',
        ];
    }
}
