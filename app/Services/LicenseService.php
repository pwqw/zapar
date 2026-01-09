<?php

namespace App\Services;

use App\Exceptions\FailedToActivateLicenseException;
use App\Http\Integrations\LemonSqueezy\LemonSqueezyConnector;
use App\Http\Integrations\LemonSqueezy\Requests\ActivateLicenseRequest;
use App\Http\Integrations\LemonSqueezy\Requests\DeactivateLicenseRequest;
use App\Http\Integrations\LemonSqueezy\Requests\ValidateLicenseRequest;
use App\Models\License;
use App\Services\License\Contracts\LicenseServiceInterface;
use App\Values\License\LicenseInstance;
use App\Values\License\LicenseMeta;
use App\Values\License\LicenseStatus;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;
use Throwable;

class LicenseService implements LicenseServiceInterface
{
    public function __construct(private readonly LemonSqueezyConnector $connector, private ?string $hashSalt = null)
    {
        $this->hashSalt ??= config('app.key');
    }

    public function activate(string $key): License
    {
        // License system disabled - always return a dummy license without API calls
        // try {
        //     $result = $this->connector->send(new ActivateLicenseRequest($key))->object();
        //
        //     if ($result->meta->store_id !== config('lemonsqueezy.store_id')) {
        //         throw new FailedToActivateLicenseException('This license key is not from Koel's official store.');
        //     }
        //
        //     $license = $this->updateOrCreateLicenseFromApiResponseBody($result);
        //     self::cacheStatus(LicenseStatus::valid($license));
        //
        //     return $license;
        // } catch (RequestException $e) {
        //     throw FailedToActivateLicenseException::fromRequestException($e);
        // } catch (Throwable $e) {
        //     Log::error($e);
        //     throw FailedToActivateLicenseException::fromThrowable($e);
        // }
        
        // Return existing license or create a dummy one
        $license = License::query()->latest()->first();
        if (!$license) {
            $dummyKey = 'dummy-license-key-' . uniqid();
            $license = License::query()->updateOrCreate([
                'hash' => sha1($dummyKey . $this->hashSalt),
            ], [
                'key' => $dummyKey,
                'instance' => LicenseInstance::make(
                    id: uniqid('instance-', true),
                    name: 'Koel Plus',
                    createdAt: now()
                ),
                'meta' => LicenseMeta::make(
                    customerId: 0,
                    customerName: 'System',
                    customerEmail: 'system@koel.local'
                ),
                'expires_at' => null,
            ]);
        }
        self::cacheStatus(LicenseStatus::valid($license));
        return $license;
    }

    public function deactivate(License $license): void
    {
        // License system disabled - no API calls
        // try {
        //     $result = $this->connector->send(new DeactivateLicenseRequest($license))->object();
        //
        //     if ($result->deactivated) {
        //         self::deleteLicense($license);
        //     }
        // } catch (RequestException $e) {
        //     if ($e->getStatus() === Response::HTTP_NOT_FOUND) {
        //         // The instance ID was not found. The license record must be a leftover from an erroneous attempt.
        //         self::deleteLicense($license);
        //
        //         return;
        //     }
        //
        //     throw FailedToActivateLicenseException::fromRequestException($e);
        // } catch (Throwable $e) {
        //     Log::error($e);
        //     throw $e;
        // }
        
        // No-op: license system disabled
    }

    public function getStatus(bool $checkCache = true): LicenseStatus
    {
        // License system disabled - always return valid status without API calls
        if ($checkCache && Cache::has('license_status')) {
            $cached = Cache::get('license_status');
            // Ensure cached status is valid
            if ($cached instanceof LicenseStatus && $cached->isValid()) {
                return $cached;
            }
        }

        /** @var ?License $license */
        $license = License::query()->latest()->first();

        if (!$license) {
            // Create a dummy license if none exists
            $dummyKey = 'dummy-license-key-' . uniqid();
            $license = License::query()->updateOrCreate([
                'hash' => sha1($dummyKey . $this->hashSalt),
            ], [
                'key' => $dummyKey,
                'instance' => LicenseInstance::make(
                    id: uniqid('instance-', true),
                    name: 'Koel Plus',
                    createdAt: now()
                ),
                'meta' => LicenseMeta::make(
                    customerId: 0,
                    customerName: 'System',
                    customerEmail: 'system@koel.local'
                ),
                'expires_at' => null,
            ]);
        }

        // Return valid status without API call
        // try {
        //     $result = $this->connector->send(new ValidateLicenseRequest($license))->object();
        //     $updatedLicense = $this->updateOrCreateLicenseFromApiResponseBody($result);
        //
        //     return self::cacheStatus(LicenseStatus::valid($updatedLicense));
        // } catch (RequestException $e) {
        //     if ($e->getStatus() === Response::HTTP_BAD_REQUEST || $e->getStatus() === Response::HTTP_NOT_FOUND) {
        //         return self::cacheStatus(LicenseStatus::invalid($license));
        //     }
        //
        //     throw $e;
        // } catch (DecryptException) {
        //     // the license key has been tampered with somehow
        //     return self::cacheStatus(LicenseStatus::invalid($license));
        // } catch (Throwable $e) {
        //     Log::error($e);
        //
        //     return LicenseStatus::unknown($license);
        // }
        
        return self::cacheStatus(LicenseStatus::valid($license));
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    private function updateOrCreateLicenseFromApiResponseBody(object $body): License
    {
        return License::query()->updateOrCreate([
            'hash' => sha1($body->license_key->key . $this->hashSalt),
        ], [
            'key' => $body->license_key->key,
            'instance' => LicenseInstance::fromJsonObject($body->instance),
            'meta' => LicenseMeta::fromJson($body->meta),
            'created_at' => $body->license_key->created_at,
            'expires_at' => $body->license_key->expires_at,
        ]);
    }

    private static function deleteLicense(License $license): void
    {
        $license->delete();
        Cache::delete('license_status');
    }

    private static function cacheStatus(LicenseStatus $status): LicenseStatus
    {
        Cache::put('license_status', $status, now()->addWeek());

        return $status;
    }

    public function isPlus(): bool
    {
        // License system disabled - always return true
        return true;
        // return $this->getStatus()->isValid();
    }

    public function isCommunity(): bool
    {
        // License system disabled - always return false
        return false;
        // return !$this->isPlus();
    }
}
