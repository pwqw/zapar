<?php

namespace Tests\Feature;

use App\Models\Podcast;
use App\Models\RadioStation;
use App\Models\Song;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_artist;
use function Tests\create_manager;

class ManagerAccessTest extends TestCase
{
    #[Test]
    public function managerCanEditContentUploadedByThemselves(): void
    {
        $manager = create_manager();
        $artist = create_artist();
        $manager->managedArtists()->attach($artist);

        // Manager sube una canción como el artista
        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $manager->id,
        ]);

        $this->assertTrue($manager->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertTrue($manager->can('edit', $song));
    }

    #[Test]
    public function managerCanEditContentUploadedByArtist(): void
    {
        $manager = create_manager();
        $artist = create_artist();
        $manager->managedArtists()->attach($artist);

        // Artista sube su propia canción
        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $artist->id,
        ]);

        $this->assertTrue($manager->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertTrue($manager->can('edit', $song));
    }

    #[Test]
    public function singleManagerCanEditAllArtistContent(): void
    {
        $manager = create_manager();
        $artist = create_artist();
        $manager->managedArtists()->attach($artist);

        // Otro usuario (no manager del artista) sube contenido como el artista
        $otherUser = create_manager();
        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $otherUser->id,
        ]);

        // Como el artista solo tiene 1 manager, puede editar TODO su contenido
        $this->assertTrue($manager->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertTrue($manager->can('edit', $song));
    }

    #[Test]
    public function multipleManagersCannotEditEachOthersContent(): void
    {
        $manager1 = create_manager();
        $manager2 = create_manager();
        $artist = create_artist();

        // Artista tiene 2 managers
        $manager1->managedArtists()->attach($artist);
        $manager2->managedArtists()->attach($artist);

        // Manager2 sube una canción como el artista
        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $manager2->id,
        ]);

        // Manager1 NO puede editar contenido subido por Manager2
        $this->assertFalse($manager1->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertFalse($manager1->can('edit', $song));

        // Pero Manager2 sí puede editar su propio contenido
        $this->assertTrue($manager2->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertTrue($manager2->can('edit', $song));
    }

    #[Test]
    public function multipleManagersCanEditArtistOwnContent(): void
    {
        $manager1 = create_manager();
        $manager2 = create_manager();
        $artist = create_artist();

        // Artista tiene 2 managers
        $manager1->managedArtists()->attach($artist);
        $manager2->managedArtists()->attach($artist);

        // Artista sube su propia canción
        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $artist->id,
        ]);

        // Ambos managers pueden editar contenido que el artista subió
        $this->assertTrue($manager1->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertTrue($manager1->can('edit', $song));

        $this->assertTrue($manager2->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertTrue($manager2->can('edit', $song));
    }

    #[Test]
    public function managerCanDeleteSongFollowingSameRules(): void
    {
        $manager1 = create_manager();
        $manager2 = create_manager();
        $artist = create_artist();

        $manager1->managedArtists()->attach($artist);
        $manager2->managedArtists()->attach($artist);

        // Manager2 sube una canción
        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $manager2->id,
        ]);

        // Manager1 NO puede borrar contenido de Manager2
        $this->assertFalse($manager1->can('delete', $song));

        // Manager2 SÍ puede borrar su propio contenido
        $this->assertTrue($manager2->can('delete', $song));
    }

    #[Test]
    public function managerCanEditRadioStationFollowingSameRules(): void
    {
        $manager1 = create_manager();
        $manager2 = create_manager();
        $artist = create_artist();

        $manager1->managedArtists()->attach($artist);
        $manager2->managedArtists()->attach($artist);

        // Manager2 crea una radio como el artista
        $station = RadioStation::factory()->create([
            'user_id' => $artist->id,
            'uploaded_by_id' => $manager2->id,
        ]);

        // Manager1 NO puede editar
        $this->assertFalse($manager1->can('edit', $station));

        // Manager2 SÍ puede editar
        $this->assertTrue($manager2->can('edit', $station));

        // Pero si el artista creó la radio, ambos pueden editar
        $artistStation = RadioStation::factory()->create([
            'user_id' => $artist->id,
            'uploaded_by_id' => $artist->id,
        ]);

        $this->assertTrue($manager1->can('edit', $artistStation));
        $this->assertTrue($manager2->can('edit', $artistStation));
    }

    #[Test]
    public function managerCanEditPodcastAddedByTheirArtist(): void
    {
        $manager = create_manager();
        $artist = create_artist();
        $manager->managedArtists()->attach($artist);

        // Artista agrega un podcast
        $podcast = Podcast::factory()->create([
            'added_by' => $artist->id,
        ]);

        $this->assertTrue($manager->can('edit', $podcast));
    }

    #[Test]
    public function managerCannotEditPodcastAddedByOtherManager(): void
    {
        $manager1 = create_manager();
        $manager2 = create_manager();
        $artist = create_artist();

        $manager1->managedArtists()->attach($artist);
        $manager2->managedArtists()->attach($artist);

        // Manager2 agrega un podcast (no hay restricción en Podcast como en Song/Radio)
        // porque Podcast no tiene uploaded_by_id, solo added_by
        $podcast = Podcast::factory()->create([
            'added_by' => $manager2->id,
        ]);

        // Manager1 NO puede editar podcast agregado por Manager2
        $this->assertFalse($manager1->can('edit', $podcast));

        // Manager2 SÍ puede editar
        $this->assertTrue($manager2->can('edit', $podcast));
    }

    #[Test]
    public function managerCannotEditContentOfNonManagedArtist(): void
    {
        $manager = create_manager();
        $artist = create_artist();
        // NO asignamos el artista al manager

        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $artist->id,
        ]);

        $this->assertFalse($manager->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertFalse($manager->can('edit', $song));
    }

    #[Test]
    public function artistCanAlwaysEditTheirOwnContent(): void
    {
        $artist = create_artist();

        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => $artist->id,
        ]);

        $this->assertTrue($artist->can('edit', $song));

        $station = RadioStation::factory()->create([
            'user_id' => $artist->id,
            'uploaded_by_id' => $artist->id,
        ]);

        $this->assertTrue($artist->can('edit', $station));
    }

    #[Test]
    public function managerCanEditLegacyContentWithoutUploadedBy(): void
    {
        $manager = create_manager();
        $artist = create_artist();
        $manager->managedArtists()->attach($artist);

        // Contenido legacy sin uploaded_by_id
        $song = Song::factory()->create([
            'owner_id' => $artist->id,
            'uploaded_by_id' => null,
        ]);

        $this->assertTrue($manager->canEditArtistContent($artist, $song->uploaded_by_id));
        $this->assertTrue($manager->can('edit', $song));
    }
}
