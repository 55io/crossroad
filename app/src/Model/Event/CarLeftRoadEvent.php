<?php

namespace Src\Model\Event;

final class CarLeftRoadEvent extends AbstractCarEvent
{
    public static function getName(): string
    {
        return 'Car left the road';
    }

    public function getTargetData(): array
    {
        return $this->getCarData();
    }

    public function getLinkedObjectData(): ?array
    {
        return [
            'road' => $this->getRoadData(),
        ];
    }
}