<?php

namespace App\Enums;

enum BillingInterval: string
{
    case Monthly = 'month';
    case Yearly = 'year';
}
