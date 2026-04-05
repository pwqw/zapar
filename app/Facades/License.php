<?php

namespace App\Facades;

use App\Services\License\Contracts\LicenseServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin LicenseServiceInterface
 */
class License extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LicenseServiceInterface::class;
    }
}
