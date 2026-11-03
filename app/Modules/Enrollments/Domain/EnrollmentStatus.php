<?php

namespace App\Modules\Enrollments\Domain;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
}
