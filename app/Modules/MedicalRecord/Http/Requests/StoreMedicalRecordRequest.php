<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Enums\MedicalRecordType;
use App\Modules\MedicalRecord\Http\Requests\Concerns\MedicalRecordValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMedicalRecordRequest extends FormRequest
{
    use MedicalRecordValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(
            [
                'patient_id' => ['required', 'integer', 'exists:pacientes,id'],
                'type' => ['required', 'string', Rule::in(array_column(MedicalRecordType::cases(), 'value'))],
                'based_on_record_id' => ['nullable', 'integer', 'exists:prontuarios,id'],
            ],
            $this->anthropometryRules(),
            $this->physicalExamRules(),
            $this->problemListRules(),
            $this->riskScoresRules(),
            $this->conductRules(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            [
                'patient_id.required' => 'O campo paciente é obrigatório.',
                'patient_id.exists' => 'Paciente não encontrado.',
                'type.required' => 'O tipo de consulta é obrigatório.',
                'type.in' => 'Tipo de consulta inválido.',
                'based_on_record_id.exists' => 'Prontuário base não encontrado.',
                'risk_scores.required_if' => 'Os escores de risco são obrigatórios para avaliação pré-anestésica.',
            ],
            $this->sharedMessages(),
        );
    }
}
