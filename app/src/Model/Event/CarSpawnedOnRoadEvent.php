<?php

namespace Src\Model\Event;

final class CarSpawnedOnRoadEvent extends AbstractCarEvent
{
    public static function getName(): string
    {
        return 'Car spawned on road';
    }

    public function getTargetData(): array
    {
        return $this->getCarData();
    }

    public function getLinkedObjectData(): ?array
    {
        return [
            'road' => $this->getRoadData()
        ];
    }
}