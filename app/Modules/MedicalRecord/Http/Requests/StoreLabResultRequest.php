<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Http\Requests;

use App\Modules\MedicalRecord\Rules\AttachmentLinkable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreLabResultRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $medicalRecordId = $this->route('medicalRecordId');

        $anexoRules = ['nullable', 'integer'];

        if ($user !== null && $medicalRecordId !== null) {
            $anexoRules[] = new AttachmentLinkable(
                prontuarioId: (int) $medicalRecordId,
                doctorUserId: (int) $user->id,
                allowMultipleLinks: true,
            );
        }

        return [
            'date' => ['required', 'date', 'before_or_equal:today'],

            'anexo_id' => $anexoRules,

            'panels' => ['nullable', 'array'],
            'panels.*.panel_id' => [
                'required',
                'string',
                Rule::exists('paineis_laboratoriais', 'id'),
            ],
            'panels.*.panel_name' => ['required', 'string', 'max:255'],
            'panels.*.is_custom' => ['nullable', 'boolean'],
            'panels.*.values' => ['required', 'array', 'min:1'],
            'panels.*.values.*.analyte_id' => [
                'required',
                'string',
                Rule::exists('catalogo_exames_laboratoriais', 'id'),
            ],
            'panels.*.values.*.value' => ['required', 'string', 'max:255'],

            'loose' => ['nullable', 'array'],
            'loose.*.name' => ['required', 'string', 'max:255'],
            'loose.*.value' => ['required', 'string', 'max:255'],
            'loose.*.unit' => ['required', 'string', 'max:50'],
            'loose.*.reference_range' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'A data de coleta é obrigatória.',
            'date.date' => 'A data de coleta deve ser uma data válida.',
            'date.before_or_equal' => 'A data de coleta não pode ser futura.',
            'anexo_id.integer' => 'O identificador do anexo deve ser um número inteiro.',
            'panels.*.panel_id.required' => 'O ID do painel é obrigatório.',
            'panels.*.panel_id.exists' => 'O painel informado não existe.',
            'panels.*.panel_name.required' => 'O nome do painel é obrigatório.',
            'panels.*.values.required' => 'Cada painel deve ter ao menos um valor.',
            'panels.*.values.min' => 'Cada painel deve ter ao menos um valor.',
            'panels.*.values.*.analyte_id.required' => 'O ID do analito é obrigatório.',
            'panels.*.values.*.analyte_id.exists' => 'O analito informado não existe no catálogo.',
            'panels.*.values.*.value.required' => 'O valor do resultado é obrigatório.',
            'loose.*.name.required' => 'O nome do exame avulso é obrigatório.',
            'loose.*.value.required' => 'O valor do resultado é obrigatório.',
            'loose.*.unit.required' => 'A unidade de medida é obrigatória.',
        ];
    }

    /**
     * Custom validation: at least one panel or one loose entry must be present.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $panels = $this->input('panels', []);
            $loose = $this->input('loose', []);

            if (empty($panels) && empty($loose)) {
                $validator->errors()->add(
                    'panels',
                    'É necessário informar ao menos um painel ou um exame avulso.',
                );
            }
        });
    }
}
