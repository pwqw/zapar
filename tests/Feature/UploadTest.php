<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Setting;
use App\Models\Song;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_artist;
use function Tests\test_path;

class UploadTest extends TestCase
{
    private UploadedFile $file;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', public_path('sandbox/media'));
        $sourcePath = test_path('songs/full.mp3');
        if (!is_file($sourcePath)) {
            $sourcePath = test_path('songs/blank.mp3');
        }
        if (!is_file($sourcePath)) {
            self::markTestSkipped('Requires tests/songs/full.mp3 or tests/songs/blank.mp3');
        }
        $tempPath = artifact_path('tmp/upload-test-' . uniqid() . '.mp3');
        File::ensureDirectoryExists(dirname($tempPath));
        File::copy($sourcePath, $tempPath);
        $this->file = new UploadedFile($tempPath, 'song.mp3', 'audio/mpeg', \UPLOAD_ERR_OK, true);
    }

    #[Test]
    public function upload(): void
    {
        $user = create_artist();

        $this->postAs('api/upload', ['file' => $this->file], $user)->assertSuccessful();
        self::assertDirectoryExists(public_path("sandbox/media/__KOEL_UPLOADS_\${$user->id}__"));
        self::assertFileExists(public_path("sandbox/media/__KOEL_UPLOADS_\${$user->id}__/song.mp3"));

        /** @var Song $song */
        $song = Song::query()->latest()->first();
        self::assertSame($song->owner_id, $user->id);
        self::assertFalse($song->is_public);
    }
}
