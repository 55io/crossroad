<?php

namespace Src\Service\Check;

use Src\Model\Car;

trait CheckSpeedTrait
{
    protected static function resolveSpeed(Car $car, int $speed = null): int
    {
        return max($car->getSpeed(), $speed);
    }
}