<?php

declare(strict_types=1);

namespace App\Modules\MedicalRecord\Enums;

enum ProcessingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Confirmed = 'confirmed';

    /**
     * Whether the attachment can be confirmed by the doctor.
     */
    public function canBeConfirmed(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Whether the attachment can still be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this !== self::Confirmed;
    }
}
