<?php

namespace App\Modules\Scheduling\Domain;

enum Modality: string
{
    case InPerson = 'in_person';
    case Virtual = 'virtual';
    case Hybrid = 'hybrid';
}
