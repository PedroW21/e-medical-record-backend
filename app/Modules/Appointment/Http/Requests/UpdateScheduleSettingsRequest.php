<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateScheduleSettingsRequest extends FormRequest
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
            'doctor_id' => ['nullable', 'integer', 'exists:users,id'],
            'blocks' => ['present', 'array'],
            'blocks.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'blocks.*.start_time' => ['required', 'date_format:H:i'],
            'blocks.*.end_time' => ['required', 'date_format:H:i', 'after:blocks.*.start_time'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'blocks.present' => 'Os blocos de horário são obrigatórios.',
            'blocks.*.day_of_week.required' => 'O dia da semana é obrigatório.',
            'blocks.*.day_of_week.between' => 'O dia da semana deve estar entre 0 (domingo) e 6 (sábado).',
            'blocks.*.start_time.required' => 'O horário de início é obrigatório.',
            'blocks.*.start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'blocks.*.end_time.required' => 'O horário de fim é obrigatório.',
            'blocks.*.end_time.date_format' => 'O horário de fim deve estar no formato HH:MM.',
            'blocks.*.end_time.after' => 'O horário de fim deve ser posterior ao horário de início.',
        ];
    }
}
