<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use App\Modules\Appointment\Enums\AppointmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAppointmentRequest extends FormRequest
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
            'patient_id' => ['nullable', 'integer', 'exists:pacientes,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'type' => ['required', 'string', Rule::in(array_column(AppointmentType::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:1000'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.exists' => 'O paciente informado não existe.',
            'date.required' => 'O campo data é obrigatório.',
            'date.date' => 'O campo data deve ser uma data válida.',
            'date.after_or_equal' => 'A data deve ser igual ou posterior a hoje.',
            'time.required' => 'O campo horário é obrigatório.',
            'time.regex' => 'O campo horário deve estar no formato HH:MM.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo de consulta informado é inválido.',
            'notes.max' => 'O campo observações não pode ter mais de 1000 caracteres.',
            'doctor_id.exists' => 'O médico informado não existe.',
        ];
    }
}
