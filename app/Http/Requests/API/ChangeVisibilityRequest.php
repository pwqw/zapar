<?php

namespace App\Http\Requests\API;

use App\Models\Podcast;
use App\Models\Song;
use Illuminate\Validation\Rule;

/**
 * @property-read array<string>|null $songs
 * @property-read array<string>|null $podcasts
 */
class ChangeVisibilityRequest extends Request
{
    /** @inheritdoc */
    public function rules(): array
    {
        return [
            'songs' => ['nullable', 'array', Rule::exists(Song::class, 'id')],
            'podcasts' => ['nullable', 'array', Rule::exists(Podcast::class, 'id')],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @inheritdoc */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (empty($this->songs) && empty($this->podcasts)) {
                $validator->errors()->add('songs', 'At least one of songs or podcasts is required.');
            }
        });
    }
}
