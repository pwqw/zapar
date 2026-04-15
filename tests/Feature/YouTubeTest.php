<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\YouTubeService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YouTubeTest extends TestCase
{
    private MockInterface $youTubeService;

    public function setUp(): void
    {
        putenv('YOUTUBE_API_KEY=test-key');
        parent::setUp();
        config(['koel.services.youtube.key' => 'test-key']);

        $this->youTubeService = $this->mock(YouTubeService::class);
    }

    #[Test]
    public function searchYouTubeVideos(): void
    {
        $song = Song::factory()->createOne();

        $this->youTubeService->expects('searchVideosRelatedToSong')->with(Mockery::on($song->is(...)), 'foo');

        $this->getAs("/api/youtube/search/song/{$song->id}?pageToken=foo")->assertOk();
    }
}
