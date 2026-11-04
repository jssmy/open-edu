<?php

arch('Domain y Application no importan Illuminate')
    ->expect(['App\Modules\Identity\Domain', 'App\Modules\Courses\Domain', 'App\Modules\Students\Domain',
        'App\Modules\Enrollments\Domain', 'App\Modules\Assessments\Domain', 'App\Modules\Progress\Domain',
        'App\Modules\Scheduling\Domain',
        'App\Modules\Identity\Application', 'App\Modules\Courses\Application', 'App\Modules\Students\Application',
        'App\Modules\Enrollments\Application', 'App\Modules\Assessments\Application',
        'App\Modules\Progress\Application', 'App\Modules\Reports\Application',
        'App\Modules\Scheduling\Application'])
    ->not->toUse('Illuminate')
    ->ignoring('App\Shared');
