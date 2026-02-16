<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum AppointmentType: string
{
    case Consultation = 'consultation';
    case FollowUp = 'follow_up';
    case Exams = 'exams';
    case FirstConsultation = 'first_consultation';

    public function label(): string
    {
        return match ($this) {
            self::Consultation => 'Consulta',
            self::FollowUp => 'Retorno',
            self::Exams => 'Exames',
            self::FirstConsultation => 'Primeira Consulta',
        };
    }
}
