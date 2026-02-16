<?php

declare(strict_types=1);

namespace App\Modules\Paciente\Http\Requests;

use App\Modules\Paciente\Enums\IntensidadeHabito;
use App\Modules\Paciente\Enums\TipoSanguineo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePacienteRequest extends FormRequest
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
        $userId = $this->user()?->id;
        $pacienteId = (int) $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                'max:14',
                Rule::unique('pacientes', 'cpf')
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->ignore($pacienteId),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'blood_type' => ['nullable', 'string', Rule::in(array_column(TipoSanguineo::cases(), 'value'))],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'chronic_conditions' => ['nullable', 'array'],
            'chronic_conditions.*' => ['string', 'max:255'],
            'medical_history' => ['nullable', 'array'],
            'medical_history.smoking' => ['nullable', 'string', Rule::in(array_column(IntensidadeHabito::cases(), 'value'))],
            'medical_history.alcohol' => ['nullable', 'string', Rule::in(array_column(IntensidadeHabito::cases(), 'value'))],
            'address' => ['nullable', 'array'],
            'address.cep' => ['required_with:address', 'string', 'max:10'],
            'address.street' => ['required_with:address', 'string', 'max:255'],
            'address.number' => ['required_with:address', 'string', 'max:20'],
            'address.complement' => ['nullable', 'string', 'max:255'],
            'address.neighborhood' => ['required_with:address', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.state' => ['required_with:address', 'string', 'size:2'],
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
            'cpf.required' => 'O campo CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está cadastrado para este médico.',
            'phone.required' => 'O campo telefone é obrigatório.',
            'email.email' => 'O campo e-mail deve ser um endereço de e-mail válido.',
            'birth_date.required' => 'O campo data de nascimento é obrigatório.',
            'birth_date.date' => 'O campo data de nascimento deve ser uma data válida.',
            'birth_date.before' => 'A data de nascimento deve ser anterior a hoje.',
            'gender.required' => 'O campo sexo é obrigatório.',
            'gender.in' => 'O campo sexo deve ser "male" ou "female".',
            'blood_type.in' => 'O tipo sanguíneo informado é inválido.',
            'status.in' => 'O status informado é inválido.',
            'medical_history.smoking.in' => 'O valor do histórico de tabagismo é inválido.',
            'medical_history.alcohol.in' => 'O valor do histórico de álcool é inválido.',
            'address.cep.required_with' => 'O campo CEP é obrigatório quando o endereço é informado.',
            'address.street.required_with' => 'O campo logradouro é obrigatório quando o endereço é informado.',
            'address.number.required_with' => 'O campo número é obrigatório quando o endereço é informado.',
            'address.neighborhood.required_with' => 'O campo bairro é obrigatório quando o endereço é informado.',
            'address.city.required_with' => 'O campo cidade é obrigatório quando o endereço é informado.',
            'address.state.required_with' => 'O campo estado é obrigatório quando o endereço é informado.',
            'address.state.size' => 'O campo estado deve ter exatamente 2 caracteres.',
        ];
    }
}
