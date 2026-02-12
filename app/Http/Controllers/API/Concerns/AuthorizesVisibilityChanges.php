<?php

namespace App\Http\Controllers\API\Concerns;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesVisibilityChanges
{
    protected function authorizeVisibilityChange(
        Model $resource,
        bool $isPublic,
        string $publishAbility = 'publish',
        string $privatizeAbility = 'edit',
    ): void {
        $this->authorize($isPublic ? $publishAbility : $privatizeAbility, $resource);
    }

    protected function authorizeVisibilityChanges(
        EloquentCollection $resources,
        bool $isPublic,
        string $publishAbility = 'publish',
        string $privatizeAbility = 'edit',
    ): void {
        $resources->each(fn (Model $resource) => $this->authorizeVisibilityChange(
            resource: $resource,
            isPublic: $isPublic,
            publishAbility: $publishAbility,
            privatizeAbility: $privatizeAbility,
        ));
    }
}
