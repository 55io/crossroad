<?php
namespace Src\Service\Check;

use Src\Model\Car;
use Src\Model\Lane;

class MaxSpeed implements CheckInterface
{
    use CheckSpeedTrait;
    public static function check(Car $car, Lane $lane, int $speed = null): bool
    {
        $speed = self::resolveSpeed($car, $speed);
        $maxSpeed = min($lane->getMaxSpeed(), $car->getMaxSpeed());

        return $speed < $maxSpeed;
    }
}