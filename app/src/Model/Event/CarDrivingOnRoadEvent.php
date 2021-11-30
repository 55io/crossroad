<?php

namespace Src\Model\Event;

final class CarDrivingOnRoadEvent extends AbstractCarEvent
{
    public static function getName(): string
    {
        return 'Car driving on road';
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