<?php

namespace App\Enums;

enum PaymentEntitlementState: string
{
    case ACTIVE = 'active';
    case REVOKED = 'revoked';
}
