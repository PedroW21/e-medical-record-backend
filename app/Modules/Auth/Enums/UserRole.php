<?php

declare(strict_types=1);

namespace App\Modules\Auth\Enums;

enum UserRole: string
{
    case Doctor = 'doctor';
    case Secretary = 'secretary';
}
