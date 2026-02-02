<?php

namespace App\Observers;

use App\Models\Song;
use Illuminate\Support\Facades\File;

class SongObserver
{
    public function updating(Song $song): void
    {
        if (!$song->isDirty('cover')) {
            return;
        }

        $oldCover = $song->getRawOriginal('cover');

        rescue_if($oldCover, static function () use ($oldCover): void {
            File::delete(image_storage_path($oldCover));
        });
    }

    public function deleted(Song $song): void
    {
        $coverPath = image_storage_path($song->cover);

        rescue_if($coverPath, static fn () => File::delete($coverPath));
    }
}
