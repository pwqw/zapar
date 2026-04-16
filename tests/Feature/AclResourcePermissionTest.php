<?php

namespace Tests\Feature;

use App\Models\Podcast;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AclResourcePermissionTest extends TestCase
{
    #[Test]
    public function podcastDeletePermissionCanBeChecked(): void
    {
        $owner = create_user();
        $podcast = Podcast::factory()->create([
            'added_by' => $owner->id,
        ]);

        $this->getAs("api/acl/permissions/podcast/{$podcast->id}/delete", $owner)
            ->assertOk()
            ->assertJsonPath('type', 'resource-permissions')
            ->assertJsonPath('allowed', true)
            ->assertJsonPath('context.type', 'podcast')
            ->assertJsonPath('context.id', $podcast->id)
            ->assertJsonPath('context.action', 'delete');
    }
}
