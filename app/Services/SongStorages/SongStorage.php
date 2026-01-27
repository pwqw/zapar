<?php

namespace App\Services\SongStorages;

use App\Enums\SongStorageType;
use App\Models\User;
use App\Values\UploadReference;

abstract class SongStorage
{
    abstract public function getStorageType(): SongStorageType;

    abstract public function storeUploadedFile(string $uploadedFilePath, User $uploader): UploadReference;

    abstract public function undoUpload(UploadReference $reference): void;

    abstract public function delete(string $location, bool $backup = false): void;

    abstract public function testSetup(): void;

    public function assertSupported(): void
    {
        // No license gate — all storage types supported
    }
}
