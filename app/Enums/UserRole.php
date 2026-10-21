<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Support = 'support';
    case Customer = 'customer';
}
