<?php

namespace App\Enums;

enum EnrollmentAccessStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
}
