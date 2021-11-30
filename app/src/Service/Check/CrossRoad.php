<?php
namespace Src\Service\Check;

use Src\Model\Car;
use Src\Model\Lane;

class CrossRoad implements CheckInterface
{
    public static function check(Car $car, Lane $lane, int $speed = null): bool
    {
        $crossRoadStartPosition = $lane->getCrossRoadStartPosition();
        $crossRoadEndPosition = $lane->getCrossRoadEndPosition();
        $carPosition = $car->getPositionOnLane();

        if($carPosition === $crossRoadStartPosition) {
            self::handleCarBeforeCrossroad($car, $lane->getRoad()->getCrossRoad());
            return false;
        }

        if($carPosition < $crossRoadStartPosition) {
            return CrossRoadDistance::check($car, $lane, $speed);
        }

        if($carPosition > $crossRoadStartPosition && $carPosition - $car->getLength() < $crossRoadEndPosition) {
            self::handleCarOnCrossroad($car, $lane->getRoad()->getCrossRoad());
        }

        return true;
    }

    private static function handleCarBeforeCrossroad($car, \Src\Model\CrossRoad $crossRoad)
    {
        $crossRoad->addStoppedCar($car);
    }

    private static function handleCarOnCrossroad($car, \Src\Model\CrossRoad $crossRoad)
    {
        $crossRoad->addDrivingCar($car);
    }
}