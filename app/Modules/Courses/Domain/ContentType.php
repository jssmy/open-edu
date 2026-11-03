<?php

namespace App\Modules\Courses\Domain;

enum ContentType: string
{
    case Text = 'text';
    case Document = 'document';
    case Image = 'image';
    case Link = 'link';
    case Video = 'video';
}
