<?php
namespace Src\Service\Check;

use Src\Model\Car;
use Src\Model\Lane;
use Src\Service\SpeedCalculator;

class CrossRoadDistance implements CheckInterface
{
    use CheckSpeedTrait;
    public static function check(Car $car, Lane $lane, int $speed = null): bool
    {
        $speed = self::resolveSpeed($car, $speed);

        $crossRoadPosition = $lane->getCrossRoadStartPosition();
        if(!$crossRoadPosition || $car->getPositionOnLane() > $crossRoadPosition) {
            return true;
        }

        $brakeLength = SpeedCalculator::calculateBrakeLength($speed, $car->getAcc());

        $carStopPosition = $car->getPositionOnLane() + $brakeLength + $car->getSpeed();

        if($carStopPosition > $crossRoadPosition) {
            return false;
        }

        return true;
    }
}