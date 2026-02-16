<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Database\Seeders;

use App\Models\User;
use App\Modules\Appointment\Enums\AppointmentOrigin;
use App\Modules\Appointment\Enums\AppointmentStatus;
use App\Modules\Appointment\Enums\AppointmentType;
use App\Modules\Appointment\Models\Consulta;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Patient\Models\Paciente;
use Illuminate\Database\Seeder;

final class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = User::query()->where('role', UserRole::Doctor)->get();

        foreach ($doctors as $doctor) {
            $this->createAppointmentsForDoctor($doctor);
        }
    }

    private function createAppointmentsForDoctor(User $doctor): void
    {
        $patient = Paciente::query()->where('user_id', $doctor->id)->first();

        $appointments = [
            [
                'data' => now()->addDay()->format('Y-m-d'),
                'horario' => '08:00',
                'tipo' => AppointmentType::Consultation,
                'status' => AppointmentStatus::Confirmed,
                'paciente_id' => $patient?->id,
                'observacoes' => 'Consulta de rotina.',
                'origem' => AppointmentOrigin::Internal,
            ],
            [
                'data' => now()->addDay()->format('Y-m-d'),
                'horario' => '09:00',
                'tipo' => AppointmentType::FollowUp,
                'status' => AppointmentStatus::Pending,
                'paciente_id' => $patient?->id,
                'observacoes' => 'Retorno de exames laboratoriais.',
                'origem' => AppointmentOrigin::Internal,
            ],
            [
                'data' => now()->addDays(2)->format('Y-m-d'),
                'horario' => '10:00',
                'tipo' => AppointmentType::FirstConsultation,
                'status' => AppointmentStatus::Requested,
                'paciente_id' => null,
                'nome_solicitante' => 'Carlos Eduardo Souza',
                'telefone_solicitante' => '(11) 98765-1234',
                'email_solicitante' => 'carlos.souza@email.com',
                'observacoes' => 'Gostaria de agendar uma primeira consulta.',
                'origem' => AppointmentOrigin::Public,
            ],
            [
                'data' => now()->addDays(3)->format('Y-m-d'),
                'horario' => '14:00',
                'tipo' => AppointmentType::Exams,
                'status' => AppointmentStatus::Confirmed,
                'paciente_id' => $patient?->id,
                'observacoes' => null,
                'origem' => AppointmentOrigin::Internal,
            ],
            [
                'data' => now()->subDay()->format('Y-m-d'),
                'horario' => '11:00',
                'tipo' => AppointmentType::Consultation,
                'status' => AppointmentStatus::Completed,
                'paciente_id' => $patient?->id,
                'observacoes' => 'Consulta realizada com sucesso.',
                'origem' => AppointmentOrigin::Internal,
            ],
        ];

        foreach ($appointments as $data) {
            Consulta::query()->updateOrCreate(
                [
                    'user_id' => $doctor->id,
                    'data' => $data['data'],
                    'horario' => $data['horario'],
                ],
                array_merge($data, ['user_id' => $doctor->id]),
            );
        }
    }
}
