<?php

declare(strict_types=1);

namespace App\Modules\Appointment\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Solicitado',
            self::Pending => 'Pendente',
            self::Confirmed => 'Confirmado',
            self::InProgress => 'Em andamento',
            self::Completed => 'Concluído',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Statuses that block a time slot from being booked.
     *
     * @return list<self>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::Pending,
            self::Confirmed,
            self::InProgress,
            self::Completed,
        ];
    }
}
