<?php
namespace Src\Service\Check;

use Src\Model\Car;
use Src\Model\Lane;
use Src\Service\SpeedCalculator;

/**
 * Расстояние до следующей машины - берем полкорпуса
 */
class PreviousCarDistance implements CheckInterface
{
    use CheckSpeedTrait;
    public static function check(Car $car, Lane $lane, int $speed = null): bool
    {
        $speed = self::resolveSpeed($car, $speed);

        $nextCar = $lane->getPreviousCar($car);

        if(!$nextCar) {
            return true;
        }

        $brakeLength = SpeedCalculator::calculateBrakeLength($speed, $car->getAcc());
        $nextCarBrakeLength = SpeedCalculator::calculateBrakeLength($nextCar->getSpeed(), $nextCar->getAcc());

        $thisCarStopPosition = $car->getPositionOnLane() + $brakeLength;
        $nextCarStopPosition = $nextCar->getPositionOnLane() + $nextCarBrakeLength;

        if($nextCarStopPosition - $nextCar->getLength() - $thisCarStopPosition < $car->getLength()/2) {
            return false;
        }

        return true;
    }
}