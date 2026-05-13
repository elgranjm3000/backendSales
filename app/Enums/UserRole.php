<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case COMPANY = 'company';
    case SELLER = 'seller';
    case CAJERO = 'cajero';
}
