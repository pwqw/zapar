<?php

namespace Database\Seeders;

use App\Enums\Acl\Role;
use App\Models\Artist;
use App\Models\Organization;
use App\Models\RadioStation;
use App\Models\User;
use App\Services\PodcastService;
use App\Services\UploadService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder para datos de desarrollo con todas las variaciones de roles y relaciones.
 *
 * Ejecutar con: docker exec koel_dev php artisan db:seed --class=DevSampleDataSeeder
 */
class DevSampleDataSeeder extends Seeder
{
    private const TEST_SONGS = [
        'tests/songs/blank.mp3',
        'tests/songs/compilation.mp3',
        'tests/songs/full-vorbis-comments.flac',
    ];

    private const RADIO_STATIONS = [
        [
            'name' => 'BLACK JAZZ MUZIC',
            'url' => 'http://ekila1.pro-fhi.net:3210/stream',
            'description' => 'Radio de Jazz en formato MP3',
        ],
        [
            'name' => 'JIM BRICKMAN Radio',
            'url' => 'http://radio.glafir.ru:7000/easy-listen/jim-brickman-cbr',
            'description' => 'Radio en formato OPUS',
        ],
        [
            'name' => 'HEY DJ RADIO',
            'url' => 'http://nr3.newradio.it:8303/stream2',
            'description' => 'Radio en formato AAC+',
        ],
        [
            'name' => 'AAC Stream Radio',
            'url' => 'http://s1.citrus3.com:18196/stream',
            'description' => 'Radio en formato AAC',
        ],
    ];

    private const PODCAST_URLS = [
        'https://media.rss.com/terminal/feed.xml',
        'https://media.rss.com/elfantasma/feed.xml',
        'https://media.rss.com/melleefreshradio/feed.xml',
        'https://media.rss.com/encouragementbydj007/feed.xml',
    ];

    public function run(): void
    {
        $this->command->info('Creando datos de desarrollo...');

        $organization = Organization::default();

        // 1. Moderador
        $moderator = $this->createUser('Moderador', 'moderador@e.mail', Role::MODERATOR, $organization, true);
        $this->command->info('✓ Moderador creado: moderador@e.mail');

        // 2. Manager 1 (verificado)
        $manager1 = $this->createUser('Manager Verificado', 'manager1@e.mail', Role::MANAGER, $organization, true);
        $this->command->info('✓ Manager 1 (verificado) creado: manager1@e.mail');

        // 3. Manager 2 (no verificado)
        $manager2 = $this->createUser('Manager Sin Verificar', 'manager2@e.mail', Role::MANAGER, $organization, false);
        $this->command->info('✓ Manager 2 (no verificado) creado: manager2@e.mail');

        // 4. Artistas
        // Artista 1: Solo manager1 (verificado porque manager1 está verificado)
        $artist1 = $this->createUser('Artista de Manager 1', 'artista1@e.mail', Role::ARTIST, $organization, true);
        $manager1->managedArtists()->attach($artist1);
        $this->command->info('✓ Artista 1 creado: artista1@e.mail (manager: Manager 1, verificado)');

        // Artista 2: Solo manager2 (no verificado)
        $artist2 = $this->createUser('Artista de Manager 2', 'artista2@e.mail', Role::ARTIST, $organization, false);
        $manager2->managedArtists()->attach($artist2);
        $this->command->info('✓ Artista 2 creado: artista2@e.mail (manager: Manager 2, no verificado)');

        // Artista 3: Ambos managers (verificado porque tiene al manager verificado)
        $artist3 = $this->createUser('Artista Compartido', 'artista3@e.mail', Role::ARTIST, $organization, true);
        $manager1->managedArtists()->attach($artist3);
        $manager2->managedArtists()->attach($artist3);
        $this->command->info('✓ Artista 3 creado: artista3@e.mail (managers: ambos, verificado)');

        // Artista 4: Sin manager (independiente, no verificado)
        $artist4 = $this->createUser('Artista Independiente', 'artista4@e.mail', Role::ARTIST, $organization, false);
        $this->command->info('✓ Artista 4 creado: artista4@e.mail (independiente, no verificado)');

        $artists = [$artist1, $artist2, $artist3, $artist4];

        // Crear contenido para cada artista
        $radioIndex = 0;
        $podcastIndex = 0;

        foreach ($artists as $index => $artistUser) {
            $artistNumber = $index + 1;
            $this->command->info("Creando contenido para Artista {$artistNumber}...");

            // Crear Artist (entidad musical)
            $artist = $this->createArtist($artistUser);

            // Crear Album y Songs
            $this->createAlbumWithSongs($artistUser, $artist, $artistNumber);

            // Crear RadioStation
            $radioData = self::RADIO_STATIONS[$radioIndex % count(self::RADIO_STATIONS)];
            $this->createRadioStation($artistUser, $radioData, $artistNumber);
            $radioIndex++;

            // Crear Podcast
            $podcastUrl = self::PODCAST_URLS[$podcastIndex % count(self::PODCAST_URLS)];
            $this->createPodcast($artistUser, $podcastUrl, $artistNumber);
            $podcastIndex++;
        }

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('Datos de desarrollo creados exitosamente!');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('Credenciales (password: KoelIsCool):');
        $this->command->info('  - admin@koel.dev (Admin - creado por artisan init)');
        $this->command->info('  - moderador@e.mail (Moderador)');
        $this->command->info('  - manager1@e.mail (Manager verificado)');
        $this->command->info('  - manager2@e.mail (Manager no verificado)');
        $this->command->info('  - artista1@e.mail (Artista, manager1, verificado)');
        $this->command->info('  - artista2@e.mail (Artista, manager2, no verificado)');
        $this->command->info('  - artista3@e.mail (Artista, ambos managers, verificado)');
        $this->command->info('  - artista4@e.mail (Artista independiente, no verificado)');
    }

    private function createUser(string $name, string $email, Role $role, Organization $organization, bool $verified): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('KoelIsCool'),
            'organization_id' => $organization->id,
            'verified' => $verified,
            'preferences' => [],
            'remember_token' => Str::random(10),
        ]);

        $user->syncRoles($role);

        return $user;
    }

    private function createArtist(User $user): Artist
    {
        return Artist::query()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'image' => null,
        ]);
    }

    private function createAlbumWithSongs(User $owner, Artist $artist, int $artistNumber): void
    {
        $uploadService = app(UploadService::class);
        $songsCreated = 0;

        // Crear 3 canciones por artista usando diferentes archivos de test
        foreach (self::TEST_SONGS as $index => $songFile) {
            if ($index >= 3) {
                break; // Solo 3 canciones por artista
            }

            $originalPath = base_path($songFile);

            if (!File::exists($originalPath)) {
                $this->command->warn("  ⚠ Archivo {$songFile} no encontrado, saltando");
                continue;
            }

            // Copiar el archivo a temporal porque UploadService lo mueve/elimina
            $tempPath = sys_get_temp_dir() . '/' . Str::random(10) . '_' . basename($songFile);
            File::copy($originalPath, $tempPath);

            try {
                // Usar UploadService para crear la canción correctamente
                $song = $uploadService->handleUpload($tempPath, $owner);

                // Hacer la canción pública
                $song->update(['is_public' => true]);

                $songsCreated++;
            } catch (\Throwable $e) {
                $this->command->warn("  ⚠ Error al crear canción desde {$songFile}: {$e->getMessage()}");
                // Limpiar archivo temporal si falla
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
            }
        }

        $this->command->info("  ✓ {$songsCreated} canciones creadas para {$artist->name}");
    }

    private function createRadioStation(User $owner, array $radioData, int $artistNumber): void
    {
        RadioStation::query()->create([
            'user_id' => $owner->id,
            'uploaded_by_id' => $owner->id,
            'name' => "{$radioData['name']} (Artista {$artistNumber})",
            'url' => $radioData['url'],
            'description' => $radioData['description'],
            'logo' => null,
            'is_public' => true,
        ]);

        $this->command->info("  ✓ Radio '{$radioData['name']}'");
    }

    private function createPodcast(User $addedBy, string $url, int $artistNumber): void
    {
        try {
            $podcastService = app(PodcastService::class);
            $podcast = $podcastService->addPodcast($url, $addedBy);

            // Hacer todos los podcasts públicos para desarrollo
            $podcast->update(['is_public' => true]);

            $this->command->info("  ✓ Podcast '{$podcast->title}'");
        } catch (\Throwable $e) {
            $this->command->warn("  ⚠ Error al crear podcast desde {$url}: {$e->getMessage()}");
        }
    }
}
