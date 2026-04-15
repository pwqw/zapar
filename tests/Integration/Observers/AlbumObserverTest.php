<?php

namespace Tests\Integration\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Observers\AlbumObserver;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumObserverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // TestCase forgets Album `saved` listeners; re-register the real observer for these tests.
        $observer = new AlbumObserver();
        $this->app->instance(AlbumObserver::class, $observer);
        Album::observe($observer);
    }

    #[Test]
    public function dispatchJobToGenerateThumbnailUponCreation(): void
    {
        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        Album::factory()->createOne();
    }

    #[Test]
    public function dispatchJobToGenerateThumbnailUponUpdate(): void
    {
        $album = Album::factory()->createOne();

        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        $album->cover = 'new-cover.webp';
        $album->save();
    }

    #[Test]
    public function doNotDispatchJobToGenerateThumbnailIfCoverIsEmpty(): void
    {
        Dispatcher::expects('dispatch')->with(Mockery::type(GenerateAlbumThumbnailJob::class))->never();

        $album = Album::factory()->createOne(['cover' => '']);

        $album->cover = '';
        $album->save();

        Album::factory()->createOne(['cover' => '']);
    }
}
