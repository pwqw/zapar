<?php

namespace App\Enums;

enum LicenseStatus
{
    case NO_LICENSE;
    case VALID;
    case INVALID;
    case UNKNOWN;
}
