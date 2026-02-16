<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAppointmentRequest extends FormRequest
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'O campo data inicial é obrigatório.',
            'start_date.date' => 'O campo data inicial deve ser uma data válida.',
            'end_date.required' => 'O campo data final é obrigatório.',
            'end_date.date' => 'O campo data final deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'doctor_id.exists' => 'O médico informado não existe.',
        ];
    }
}
