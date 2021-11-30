<?php

namespace Src\Model\Event;

use Src\Model\Car;
use Src\Model\Lane;

abstract class AbstractCarEvent implements EventInterface
{
    /**
     * @var Car
     */
    protected $car;

    /**
     * @var Lane
     */
    protected $lane;


    public function __construct(Car $car, Lane $lane) {

        $this->car = $car;
        $this->lane = $lane;
    }

    protected function getCarData(): array
    {
        return [
            'direction' => $this->car->getDirection(),
            'plannedManeur' => $this->car->getPlannedManeur(),
            'position' => $this->car->getPositionOnLane(),
            'speed' => $this->car->getSpeed(),
            'state' => $this->car->getState()
        ];
    }

    protected function getRoadData(): ?array
    {
        return [
                'transformation' => $this->lane->getTransformation(),
                'direction' => $this->lane->getDirection()
        ];
    }
}