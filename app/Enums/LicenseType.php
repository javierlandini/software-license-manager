<?php

namespace App\Enums;

enum LicenseType: string
{
    case Perpetual = 'perpetual';
    case Subscription = 'subscription';
    case Trial = 'trial';
}
