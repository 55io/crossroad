<?php

namespace Src\Model\Event;

final class CarChangedRoadEvent extends AbstractCarEvent
{
    private $maneur = 'undefined';

    public static function getName(): string
    {
        return 'Car changed the road';
    }

    public function getTargetData(): array
    {
        return $this->getCarData();
    }

    public function getLinkedObjectData(): ?array
    {
        return [
            'nextRoad' => $this->getRoadData(),
            'currentRoad' => [
                'transformation' => $this->car->getLane()->getTransformation(),
                'direction' => $this->car->getLane()->getDirection()
            ],
            'maneur' => $this->maneur ?? 'undefined'
        ];
    }

    /**
     * @param string $maneur
     */
    public function setManeur(string $maneur): void
    {
        $this->maneur = $maneur;
    }
}