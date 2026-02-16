<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use App\Modules\Appointment\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAppointmentStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status informado é inválido.',
        ];
    }
}
