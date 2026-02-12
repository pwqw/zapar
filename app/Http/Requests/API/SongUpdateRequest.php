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
                    $user && $user->canAssignCoOwnerArtist(),
                    fn () => Rule::in($user->getAssignableArtistsForCoOwner()->pluck('id')->all()),
                    fn () => 'prohibited',
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

        return SongUpdateData::make(
            title: $this->input('data.title'),
            artistName: $this->input('data.artist_name'),
            albumName: $albumName,
            albumArtistName: $this->input('data.album_artist_name'),
            track: $this->has('data.track') ? (int) $this->input('data.track') : null,
            disc: $this->has('data.disc') ? (int) $this->input('data.disc') : null,
            genre: $this->input('data.genre'),
            year: $this->has('data.year') ? (int) $this->input('data.year') : null,
            lyrics: $this->input('data.lyrics'),
            artistUserId: $artistUserId,
            songCover: $songCover,
        );
    }
}
