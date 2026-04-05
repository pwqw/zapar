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
 * Development seeder covering role and relationship variations.
 *
 * Run: docker exec koel_dev php artisan db:seed --class=DevSampleDataSeeder
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
            'description' => 'Jazz radio (MP3)',
        ],
        [
            'name' => 'JIM BRICKMAN Radio',
            'url' => 'http://radio.glafir.ru:7000/easy-listen/jim-brickman-cbr',
            'description' => 'Radio stream (Opus)',
        ],
        [
            'name' => 'HEY DJ RADIO',
            'url' => 'http://nr3.newradio.it:8303/stream2',
            'description' => 'Radio stream (AAC+)',
        ],
        [
            'name' => 'AAC Stream Radio',
            'url' => 'http://s1.citrus3.com:18196/stream',
            'description' => 'Radio stream (AAC)',
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
        $this->command->info('Creating development data...');

        $organization = Organization::default();

        // 1. Moderator
        $moderator = $this->createUser('Moderator', 'moderador@e.mail', Role::MODERATOR, $organization, true);
        $this->command->info('✓ Moderator created: moderador@e.mail');

        // 2. Manager 1 (verified)
        $manager1 = $this->createUser('Verified Manager', 'manager1@e.mail', Role::MANAGER, $organization, true);
        $this->command->info('✓ Manager 1 (verified) created: manager1@e.mail');

        // 3. Manager 2 (unverified)
        $manager2 = $this->createUser('Unverified Manager', 'manager2@e.mail', Role::MANAGER, $organization, false);
        $this->command->info('✓ Manager 2 (unverified) created: manager2@e.mail');

        // 4. Artists
        // Artista 1: Solo manager1 (verificado porque manager1 está verificado)
        $artist1 = $this->createUser('Manager 1 Artist', 'artista1@e.mail', Role::ARTIST, $organization, true);
        $manager1->managedArtists()->attach($artist1);
        $this->command->info('✓ Artist 1 created: artista1@e.mail (manager: Manager 1, verified)');

        // Artista 2: Solo manager2 (no verificado)
        $artist2 = $this->createUser('Manager 2 Artist', 'artista2@e.mail', Role::ARTIST, $organization, false);
        $manager2->managedArtists()->attach($artist2);
        $this->command->info('✓ Artist 2 created: artista2@e.mail (manager: Manager 2, unverified)');

        // Artista 3: Ambos managers (verificado porque tiene al manager verificado)
        $artist3 = $this->createUser('Shared Artist', 'artista3@e.mail', Role::ARTIST, $organization, true);
        $manager1->managedArtists()->attach($artist3);
        $manager2->managedArtists()->attach($artist3);
        $this->command->info('✓ Artist 3 created: artista3@e.mail (managers: both, verified)');

        // Artista 4: Sin manager (independiente, no verificado)
        $artist4 = $this->createUser('Independent Artist', 'artista4@e.mail', Role::ARTIST, $organization, false);
        $this->command->info('✓ Artist 4 created: artista4@e.mail (independent, unverified)');

        // 5. Regular user
        $user = $this->createUser('Regular User', 'usuario@e.mail', Role::USER, $organization, true);
        $this->command->info('✓ Regular user created: usuario@e.mail');

        $artists = [$artist1, $artist2, $artist3, $artist4];

        // Create content for each artist
        $radioIndex = 0;
        $podcastIndex = 0;

        foreach ($artists as $index => $artistUser) {
            $artistNumber = $index + 1;
            $this->command->info("Creating content for artist {$artistNumber}...");

            // Create Artist (music entity)
            $artist = $this->createArtist($artistUser);

            // Album and songs
            $this->createAlbumWithSongs($artistUser, $artist, $artistNumber);

            // RadioStation
            $radioData = self::RADIO_STATIONS[$radioIndex % count(self::RADIO_STATIONS)];
            $this->createRadioStation($artistUser, $radioData, $artistNumber);
            $radioIndex++;

            // Podcast
            $podcastUrl = self::PODCAST_URLS[$podcastIndex % count(self::PODCAST_URLS)];
            $this->createPodcast($artistUser, $podcastUrl, $artistNumber);
            $podcastIndex++;
        }

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('Development data created successfully.');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('Credentials (password: KoelIsCool):');
        $this->command->info('  - admin@koel.dev (Admin — created by artisan init)');
        $this->command->info('  - moderador@e.mail (Moderator)');
        $this->command->info('  - manager1@e.mail (Verified manager)');
        $this->command->info('  - manager2@e.mail (Unverified manager)');
        $this->command->info('  - artista1@e.mail (Artist, manager1, verified)');
        $this->command->info('  - artista2@e.mail (Artist, manager2, unverified)');
        $this->command->info('  - artista3@e.mail (Artist, both managers, verified)');
        $this->command->info('  - artista4@e.mail (Independent artist, unverified)');
        $this->command->info('  - usuario@e.mail (Regular user)');
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

        // Genres per test song: Rock, Pop, empty
        $genres = ['Rock', 'Pop', ''];

        // Definir álbumes: Artista 1 tiene todas sus canciones en "The Record", los demás en álbum vacío
        $albumName = $artistNumber === 1 ? 'The Record' : '';

        // Get or create the target album
        $album = \App\Models\Album::getOrCreate($artist, $albumName);

        // Three songs per artist using different test fixtures
        foreach (self::TEST_SONGS as $index => $songFile) {
            if ($index >= 3) {
                break; // max 3 songs per artist
            }

            $originalPath = base_path($songFile);

            if (!File::exists($originalPath)) {
                $this->command->warn("  ⚠ File not found: {$songFile}, skipping");
                continue;
            }

            // Copy to temp; UploadService moves/deletes the path
            $tempPath = sys_get_temp_dir() . '/' . Str::random(10) . '_' . basename($songFile);
            File::copy($originalPath, $tempPath);

            try {
                // UploadService creates the song
                $song = $uploadService->handleUpload($tempPath, $owner);

                // Set metadata directly
                $song->update([
                    'title' => 'Song ' . ($index + 1),
                    'artist_id' => $artist->id,
                    'artist_name' => $owner->name,
                    'album_id' => $album->id,
                    'album_name' => $album->name,
                    'track' => $index + 1,
                    'disc' => 1,
                    'year' => 2024,
                    'lyrics' => '',
                    'is_public' => true,
                ]);

                // Sync genres (empty clears previous genres)
                $song->syncGenres($genres[$index]);

                $songsCreated++;
            } catch (\Throwable $e) {
                $this->command->warn("  ⚠ Failed to create song from {$songFile}: {$e->getMessage()}");
                // Clean up temp file on failure
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
            }
        }

        $this->command->info("  ✓ {$songsCreated} song(s) created for {$artist->name}");
    }

    private function createRadioStation(User $owner, array $radioData, int $artistNumber): void
    {
        RadioStation::query()->create([
            'user_id' => $owner->id,
            'uploaded_by_id' => $owner->id,
            'name' => "{$radioData['name']} (Artist {$artistNumber})",
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

            // Make all seeded podcasts public
            $podcast->update(['is_public' => true]);

            $this->command->info("  ✓ Podcast '{$podcast->title}'");
        } catch (\Throwable $e) {
            $this->command->warn("  ⚠ Failed to create podcast from {$url}: {$e->getMessage()}");
        }
    }
}
