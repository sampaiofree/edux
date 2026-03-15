<?php

namespace App\Enums;

enum PaymentInternalAction: string
{
    case APPROVE = 'approve';
    case REVOKE = 'revoke';
    case IGNORE = 'ignore';
}
