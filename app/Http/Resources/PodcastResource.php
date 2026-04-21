<?php

namespace App\Http\Resources;

use App\Models\Podcast;
use App\Models\PodcastUserPivot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PodcastResource extends JsonResource
{
    public const array JSON_STRUCTURE = [
        'type',
        'id',
        'url',
        'title',
        'image',
        'link',
        'description',
        'author',
    ];

    public function __construct(
        private readonly Podcast $podcast,
        private readonly bool $withSubscriptionData = true,
    ) {
        parent::__construct($this->podcast);
    }

    public static function collection($resource): PodcastResourceCollection
    {
        return PodcastResourceCollection::make($resource);
    }

    /** @inheritdoc */
    public function toArray(Request $request): array
    {
        $data = [
            'type' => 'podcast',
            'id' => $this->podcast->id,
            'url' => $this->podcast->url,
            'title' => $this->podcast->title,
            'image' => $this->podcast->image,
            'link' => $this->podcast->link,
            'description' => $this->podcast->description,
            'author' => $this->podcast->author,
            'favorite' => $this->podcast->favorite,
            'is_public' => $this->podcast->is_public,
        ];

        if ($this->withSubscriptionData) {
            /** @var User $user */
            $user = $request->user();

            $subscriber = $this->podcast->subscribers->firstWhere('id', $user->id);

            if ($subscriber !== null) {
                /** @var PodcastUserPivot $pivot */
                $pivot = $subscriber->pivot;
                $data['subscribed_at'] = $pivot->created_at;
                $data['last_played_at'] = $pivot->updated_at;
                $data['state'] = $pivot->state->toArray();
            } else {
                // Upstream/release usa sole() porque view() exige suscripción. Zapar permite ver
                // sin pivot (público/org); el cliente móvil sigue exigiendo las mismas claves.
                $data['subscribed_at'] = '';
                $data['last_played_at'] = '';
                $data['state'] = [
                    'current_episode' => null,
                    'progresses' => [],
                ];
            }
        }

        return $data;
    }
}
