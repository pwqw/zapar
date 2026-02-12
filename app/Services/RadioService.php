<?php

namespace App\Services;

use App\Models\RadioStation;
use App\Models\User;
use App\Repositories\RadioStationRepository;
use App\Values\Radio\RadioStationCreateData;
use App\Values\Radio\RadioStationUpdateData;

class RadioService
{
    public function __construct(
        private readonly RadioStationRepository $repository,
        private readonly ImageStorage $imageStorage,
    ) {
    }

    public function createRadioStation(RadioStationCreateData $dto, User $user): RadioStation
    {
        // logo is optional and not critical, so no transaction is needed
        $logoFileName = rescue_if($dto->logo, function () use ($dto) {
            return $this->imageStorage->storeImage($dto->logo);
        });

        /** @var RadioStation $station */
        $station = $user->radioStations()->create([
            'url' => $dto->url,
            'name' => $dto->name,
            'logo' => $logoFileName,
            'description' => $dto->description,
            'is_public' => $dto->isPublic,
            'uploaded_by_id' => $user->id,
        ]);

        return $this->repository->findOneWithUserContext($station->id, $user);
    }

    public function updateRadioStation(RadioStation $radioStation, RadioStationUpdateData $dto): RadioStation
    {
        $data = [
            'url' => $dto->url,
            'name' => $dto->name,
            'description' => $dto->description,
        ];

        if (is_string($dto->logo)) {
            $data['logo'] = rescue_if($dto->logo, fn () => $this->imageStorage->storeImage($dto->logo), '');
        }

        $radioStation->update($data);
        $this->setVisibility($radioStation, $dto->isPublic);

        return $this->repository->findOneWithUserContext($radioStation->id, $radioStation->user);
    }

    public function setVisibility(RadioStation $radioStation, bool $isPublic): void
    {
        if ($radioStation->is_public === $isPublic) {
            return;
        }

        $radioStation->update(['is_public' => $isPublic]);
    }
}
