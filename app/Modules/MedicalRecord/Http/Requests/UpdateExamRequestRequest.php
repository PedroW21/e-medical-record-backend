<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateExamRequestRequest extends FormRequest
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
            'model_id' => ['nullable', 'string', 'max:255'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['required_with:items', 'string', 'max:255'],
            'items.*.name' => ['required_with:items', 'string', 'max:500'],
            'items.*.tuss_code' => ['nullable', 'string', 'max:50'],
            'items.*.selected' => ['required_with:items', 'boolean'],
            'cid_10' => ['nullable', 'string', 'max:20'],
            'clinical_indication' => ['nullable', 'string', 'max:5000'],
            'medical_report' => ['nullable', 'array'],
            'medical_report.template_id' => ['nullable', 'string', 'max:255'],
            'medical_report.body' => ['required_with:medical_report', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.array' => 'O campo itens deve ser uma lista.',
            'items.min' => 'A solicitação deve conter pelo menos um item.',
            'items.*.id.required_with' => 'Cada item deve ter um identificador.',
            'items.*.id.max' => 'O identificador do item não pode ter mais de 255 caracteres.',
            'items.*.name.required_with' => 'Cada item deve ter um nome.',
            'items.*.name.max' => 'O nome do item não pode ter mais de 500 caracteres.',
            'items.*.tuss_code.max' => 'O código TUSS não pode ter mais de 50 caracteres.',
            'items.*.selected.required_with' => 'O campo selecionado é obrigatório para cada item.',
            'items.*.selected.boolean' => 'O campo selecionado deve ser verdadeiro ou falso.',
            'cid_10.max' => 'O campo CID-10 não pode ter mais de 20 caracteres.',
            'clinical_indication.max' => 'A indicação clínica não pode ter mais de 5000 caracteres.',
            'medical_report.array' => 'O campo relatório médico deve ser um objeto.',
            'medical_report.template_id.max' => 'O identificador do modelo não pode ter mais de 255 caracteres.',
            'medical_report.body.required_with' => 'O corpo do relatório médico é obrigatório quando o relatório médico é informado.',
            'medical_report.body.max' => 'O corpo do relatório médico não pode ter mais de 10000 caracteres.',
        ];
    }
}
