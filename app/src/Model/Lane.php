<?php

namespace Src\Model;

use Src\Model\Event\CarLeftRoadEvent;
use Src\Model\Event\CarSpawnedOnRoadEvent;
use Src\Model\Geometry\DirectedRectangle;
use Src\Model\Geometry\Point;
use Src\Model\Geometry\Rectangle;
use Src\Service\FileLogger;
use Src\Service\UseFileLoggerTrait;

class Lane extends DirectedRectangle
{
    use UseFileLoggerTrait;
    /**
     * @var int
     */
    private $trafficIntensity = 111;

    /**
     * @var int
     */
    private $maxSpeed = 20;

    /**
     * @var Road
     */
    private $road;


    /**
     * @var Car[]
     */
    private $carCollection = [];

    public function __construct(int $length, int $width = 10)
    {
        parent::__construct($length, $width);
    }

    public function addCar(Car $car, int $position = 0)
    {
        if ($position != 0 || $this->canAddCar()) {
            $car->setTransformation($this->transformation);
            $car->setDirection($this->direction);
            $car->setLane($this);
            $car->setPositionOnLane($position);
            $this->carCollection[] = $car;
            if($position > 0) {
                usort($this->carCollection, function (Car $firstCar, Car $secondCar) {
                    return - $firstCar->getPositionOnLane() + $secondCar->getPositionOnLane();
                });
            }

            $event = new CarSpawnedOnRoadEvent($car, $this);
            $this->logEvent($event);
        }
    }

    public function deleteCar(Car $myCar)
    {
        foreach ($this->carCollection as $index => $car) {
            if (spl_object_hash($myCar) == spl_object_hash($car)) {
                array_splice($this->carCollection, $index, 1);
                break;
            }
        }
        $event = new CarLeftRoadEvent($myCar, $this);
        $this->logEvent($event);
    }

    public function canAddCar(): bool
    {
        $lastCar = end($this->carCollection);
        return !$lastCar || $lastCar->getPositionOnLane() > $lastCar->length;
    }

    public function getPointByPosition(int $position): Point
    {
        $point = clone($this->point);
        $attr = $this->getDirectionAttribute();
        $point->$attr += $this->isReverse() ? $this->length - $position : $position;
        return $point;
    }

    public function getPreviousCar(Car $myCar): ?Car
    {
        $myCarPosition = $myCar->getPositionOnLane();
        $previousCar = null;

        foreach ($this->carCollection as $car) {
            $carPosition = $car->getPositionOnLane();
            /** @var Car $car */
            if ($carPosition > $myCarPosition && ($previousCar === null || $carPosition < $previousCar->getPositionOnLane())) {
                $previousCar = $car;
            }
        }

        return $previousCar;
    }

    public function getAvailableLeftLane(int $position): ?Lane
    {
        $crossRoad = $this->road->getCrossRoad();
        if ($crossRoad && $position >= $this->getCrossRoadStartPosition() && $position <= $this->getCrossRoadEndPosition()) {
            $crossedRoad = $this->isHorizontal() ? $crossRoad->getVerticalRoad() : $crossRoad->getHorizontalRoad();
            foreach ($crossedRoad->getLaneCollection() as $crossedLane) {
                if ($this->isVertical() && $crossedLane->isReverse() === $this->isReverse()) {
                    return $crossedLane;
                }
                if ($this->isHorizontal() && $crossedLane->isReverse() !== $this->isReverse()) {
                    return $crossedLane;
                }
            }
        }

        return null;
    }

    public function getAvailableRightLane(int $position): ?Lane
    {
        $crossRoad = $this->road->getCrossRoad();
        if ($crossRoad && $position >= $this->getCrossRoadStartPosition() && $position <= $this->getCrossRoadEndPosition()) {
            $crossedRoad = $this->isHorizontal() ? $crossRoad->getVerticalRoad() : $crossRoad->getHorizontalRoad();
            foreach ($crossedRoad->getLaneCollection() as $crossedLane) {
                if ($this->isVertical() && $crossedLane->isReverse() !== $this->isReverse()) {
                    return $crossedLane;
                }
                if ($this->isHorizontal() && $crossedLane->isReverse() === $this->isReverse()) {
                    return $crossedLane;
                }
            }
        }

        return null;
    }

    /**
     * @param Road $road
     */
    public function setRoad(Road $road): void
    {
        $this->road = $road;
    }

    public function serialize(): array
    {
        $result = parent::serialize();
        $result['trafficIntensity'] = $this->trafficIntensity;
        $result['maxSpeed'] = $this->maxSpeed;
        $result['isReverse'] = $this->isReverse();
        $result['cars'] = $this->serializeCars();

        return $result;
    }

    public function serializeCars(): array
    {
        $result = [];
        foreach ($this->carCollection as $car) {
            $result[] = $car->serialize();
        }
        return $result;
    }

    public function getCrossRoadStartPosition()
    {
        $pos = null;
        $crossRoad = $this->road->getCrossRoad();
        if ($crossRoad) {
            $crossedRoad = $this->isHorizontal() ? $crossRoad->getVerticalRoad() : $crossRoad->getHorizontalRoad();
            $attr = $this->getDirectionAttribute();
            $pos = $this->isReverse()
                ? $this->length - $crossedRoad->point->$attr - $crossedRoad->width
                : $crossedRoad->point->$attr;
        }

        return $pos;
    }

    public function getCrossRoadEndPosition()
    {
        $pos = $this->getCrossRoadStartPosition();
        if ($pos) {
            $crossRoad = $this->road->getCrossRoad();
            $crossedRoad = $this->isHorizontal() ? $crossRoad->getVerticalRoad() : $crossRoad->getHorizontalRoad();
            $pos += $crossedRoad->width;
        }

        return $pos;
    }

    /**
     * @param static $object
     * @param array $data
     * @throws \Exception
     */
    protected static function setAdditionalData(Rectangle $object, array $data, $logger = null): void
    {
        parent::setAdditionalData($object, $data);
        $object->trafficIntensity = $data['trafficIntensity'];
        $object->maxSpeed = $data['maxSpeed'];
        foreach ($data['cars'] as $carData) {
            $car = Car::fromArray($carData, $logger);
            $car->setLane($object);
            $object->carCollection[] = $car;
        }
        if($logger instanceof FileLogger) {
            $object->setLogger($logger);
        }
    }

    /**
     * @return Car[]
     */
    public function getCarCollection(): array
    {
        return $this->carCollection;
    }

    /**
     * @return Road
     */
    public function getRoad(): Road
    {
        return $this->road;
    }

    /**
     * @return int
     */
    public function getMaxSpeed(): int
    {
        return $this->maxSpeed;
    }

    /**
     * @param int $maxSpeed
     */
    public function setMaxSpeed(int $maxSpeed): void
    {
        $this->maxSpeed = $maxSpeed;
    }

    /**
     * @return int
     */
    public function getTrafficIntensity(): int
    {
        return $this->trafficIntensity;
    }

    /**
     * @param int $trafficIntensity
     */
    public function setTrafficIntensity(int $trafficIntensity): void
    {
        $this->trafficIntensity = $trafficIntensity;
    }
}