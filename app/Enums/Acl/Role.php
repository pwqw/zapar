<?php

namespace App\Enums\Acl;

use App\Exceptions\KoelPlusRequiredException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

enum Role: string implements Arrayable
{
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case MANAGER = 'manager';
    case ARTIST = 'artist';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::MODERATOR => 'Moderator',
            self::MANAGER => 'Manager',
            self::ARTIST => 'Artist',
            self::USER => 'User',
        };
    }

    public static function default(): self
    {
        return self::USER;
    }

    public function level(): int
    {
        return match ($this) {
            self::ADMIN => 5,
            self::MODERATOR => 4,
            self::MANAGER => 3,
            self::ARTIST => 2,
            self::USER => 1,
        };
    }

    public function greaterThan(self $other): bool
    {
        return $this->level() > $other->level();
    }

    public function lessThan(self $other): bool
    {
        return $this->level() < $other->level();
    }

    public function canManage(self $other): bool
    {
        return $this->level() >= $other->level();
    }

    public function available(): bool
    {
        return true; // All roles are now available
    }

    public function assertAvailable(): void
    {
        throw_unless($this->available(), KoelPlusRequiredException::class);
    }

    /** @return Collection<self> */
    public static function allAvailable(): Collection
    {
        return collect(self::cases())->filter(static fn (Role $role) => $role->available());
    }

    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Full system access and configuration.',
            self::MODERATOR => 'Manage users in their organization and publish content.',
            self::MANAGER => 'Manage a group of artists and upload content on their behalf.',
            self::ARTIST => 'Upload and manage their own content (private by default).',
            self::USER => 'Listen and download public content.',
        };
    }

    /** @inheritdoc */
    public function toArray(): array
    {
        return [
            'id' => $this->value,
            'label' => $this->label(),
            'level' => $this->level(),
            'is_default' => $this === self::default(),
            'description' => $this->description(),
        ];
    }
}
