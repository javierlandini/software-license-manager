<?php

namespace App\Enums;

enum LicenseEventType: string
{
    case Issued = 'issued';
    case Activated = 'activated';
    case Deactivated = 'deactivated';
    case Renewed = 'renewed';
    case Suspended = 'suspended';
    case Reinstated = 'reinstated';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Converted = 'converted';
}
