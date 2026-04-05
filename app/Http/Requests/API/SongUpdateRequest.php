<?php

namespace App\Http\Requests\API;

use App\Models\Song;
use App\Models\User;
use App\Rules\ValidImageData;
use App\Values\Song\SongUpdateData;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string> $songs
 * @property-read array<mixed> $data
 */
class SongUpdateRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'data' => 'required|array',
            'data.song_cover' => ['sometimes', 'nullable', 'string', new ValidImageData()],
            'data.artist_user_id' => [
                'sometimes',
                'nullable',
                'string',
                Rule::when(
                    $user && ($user->canAssignCoOwnerArtist() || $user->isArtist()),
                    function () use ($user) {
                        if ($user->canAssignCoOwnerArtist()) {
                            return Rule::in($user->getAssignableArtistsForCoOwner()->pluck('public_id')->all());
                        }

                        return Rule::in([$user->public_id, null, '']);
                    },
                    'prohibited',
                ),
            ],
            'songs' => ['required', 'array', Rule::exists(Song::class, 'id')->whereNull('podcast_id')],
        ];
    }

    public function toDto(): SongUpdateData
    {
        $artistUserId = null;
        if ($artistUserPublicId = $this->input('data.artist_user_id')) {
            $artistUser = User::where('public_id', $artistUserPublicId)->first();
            $artistUserId = $artistUser?->id;
        }

        $songCover = $this->input('data.song_cover');
        if ($songCover !== null && $songCover !== '') {
            $songCover = (string) $songCover;
        } elseif ($this->exists('data.song_cover')) {
            $songCover = '';
        } else {
            $songCover = null;
        }

        $albumName = $this->input('data.album_name');
        if ($albumName === null && $this->exists('data.album_name')) {
            $albumName = '';
        }

        $track = null;
        if ($this->has('data.track')) {
            $trackInput = $this->input('data.track');
            $track = $trackInput !== null ? (int) $trackInput : null;
        }

        $disc = null;
        if ($this->has('data.disc')) {
            $discInput = $this->input('data.disc');
            $disc = $discInput !== null ? (int) $discInput : null;
        }

        return SongUpdateData::make(
            title: $this->input('data.title'),
            artistName: $this->input('data.artist_name'),
            albumName: $albumName,
            albumArtistName: $this->input('data.album_artist_name'),
            track: $track,
            disc: $disc,
            genre: $this->input('data.genre'),
            year: $this->has('data.year') ? (int) $this->input('data.year') : null,
            lyrics: $this->input('data.lyrics'),
            artistUserId: $artistUserId,
            songCover: $songCover,
        );
    }
}
