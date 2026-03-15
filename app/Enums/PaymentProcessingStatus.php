<?php

namespace App\Enums;

enum PaymentProcessingStatus: string
{
    case QUEUED = 'queued';
    case PROCESSED = 'processed';
    case IGNORED = 'ignored';
    case PENDING = 'pending';
    case FAILED = 'failed';
}
