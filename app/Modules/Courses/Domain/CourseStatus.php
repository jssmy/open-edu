<?php

namespace App\Modules\Courses\Domain;

enum CourseStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Deactivated = 'deactivated';
}
