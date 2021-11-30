<?php

namespace Src\Model;

use Src\ArrayableInterface;
use Src\Dictionary\PlannedManeur;
use Src\Model\Geometry\DirectedRectangle;
use Src\Service\UseFileLoggerTrait;

final class CrossRoad implements ArrayableInterface
{
    use UseFileLoggerTrait;
    private string $state = 'free';
    private array $stoppedCarCollection = [];
    private array $drivingCarCollection = [];
    private Road $verticalRoad;
    private Road $horizontalRoad;
    /**
     * @var null|TrafficLight
     */
    private $trafficLight;

    public function __construct(Road $firstRoad, Road $secondRoad)
    {
        if ($firstRoad->transformation === $secondRoad->transformation) {
            throw new \Exception('Wrong road transformation');
        }

        if ($firstRoad->isHorizontal()) {
            $this->horizontalRoad = $firstRoad;
            $this->verticalRoad = $secondRoad;
        } else {
            $this->verticalRoad = $firstRoad;
            $this->horizontalRoad = $secondRoad;
        }
        $firstRoad->setCrossRoad($this);
        $secondRoad->setCrossRoad($this);
    }

    public function run()
    {
        $allCars = [];
        foreach ($this->getRoads() as $road) {
            foreach ($road->getLaneCollection() as $lane) {
                /**
                 * @var Lane $lane
                 */
                $allCars = array_merge($allCars, $lane->getCarCollection());
            }
        }

        usort($allCars, function (Car $firstCar, Car $secondCar) {
            return - $firstCar->getPositionOnLane() + $secondCar->getPositionOnLane();
        });

        foreach ($allCars as $car) {
            $car->drive();
        }


        $this->setState(empty($this->drivingCarCollection) ? 'free' : 'busy');

        $this->driveCarWithMaxPriority();
    }

    public function addStoppedCar(Car $car)
    {
        foreach ($this->stoppedCarCollection as $stoppedCar) {
            if ($this->compareCars($car, $stoppedCar)) {
                return false;
            }
        }

        $this->stoppedCarCollection[] = $car;

        return true;
    }

    public function addDrivingCar(Car $car)
    {
        foreach ($this->drivingCarCollection as $drivingCar) {
            if ($this->compareCars($car, $drivingCar)) {
                return false;
            }
        }

        $this->setState('busy');
        $this->drivingCarCollection[] = $car;

        return true;
    }

    private function compareCars(Car $firstCar, Car $secondCar): bool
    {
        return spl_object_hash($firstCar) == spl_object_hash($secondCar);
    }

    private function driveCarWithMaxPriority()
    {
        $maxPriority = 0;
        $carWithMaxPriorityIndex = null;

        if (!$this->isFree()) {
            return;
        }

        foreach ($this->stoppedCarCollection as $index => $car) {
            /** @var Car $car */
            if (
                $this->getTrafficLight()->canVerticalRoadDrive() && $car->isVertical() ||
                $this->getTrafficLight()->canHorizontalRoadDrive() && $car->isHorizontal()
            ) {
                $priority = $this->calculatePriorityForCar($car);
                if ($carWithMaxPriorityIndex === null || $priority > $maxPriority) {
                    $carWithMaxPriorityIndex = $index;
                    $maxPriority = $priority;
                }
            }
        }
        if ($carWithMaxPriorityIndex !== null) {
            $this->setState('busy');
            $carWithMaxPriority = $this->stoppedCarCollection[$carWithMaxPriorityIndex];
            $carWithMaxPriority->drive(true);
            array_splice($this->stoppedCarCollection, $carWithMaxPriorityIndex, 1);
        }
    }

    private function calculatePriorityForCar(Car $car): int
    {
        $priority = 0;
        if ($this->trafficLight) {
            $priority += $car->isVertical() && $this->trafficLight->canVerticalRoadDrive() ? 100 : 0;
            $priority += $car->isHorizontal() && $this->trafficLight->canHorizontalRoadDrive() ? 100 : 0;
        } elseif ($car->getLane()->getRoad()->hasPriority) {
            $priority += 50;
        }


        foreach ($this->stoppedCarCollection as $stoppedCar) {
            /** @var Car $stoppedCar */
            if ($this->compareCars($car, $stoppedCar)) {
                continue;
            }

            $priority += self::carHasPriority($car, $stoppedCar) ? 1 : 0;
        }

        return $priority;
    }

    public function isFree()
    {
        return $this->getState() === 'free';
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return Road
     */
    public function getHorizontalRoad(): Road
    {
        return $this->horizontalRoad;
    }

    /**
     * @return Road
     */
    public function getVerticalRoad(): Road
    {
        return $this->verticalRoad;
    }

    public function serialize(): array
    {
        $stopCars = [];
        foreach ($this->stoppedCarCollection as $car) {
            /** @var Car $car */
            $stopCars[] = [
                'car' => $car->serialize(),
                'priority' => $this->calculatePriorityForCar($car)
            ];
        }
        $data = [
            'stoppedCars' => $stopCars,
            'drivingCarCount' => count($this->drivingCarCollection),
            'verticalRoad' => $this->verticalRoad->serialize(),
            'horizontalRoad' => $this->horizontalRoad->serialize(),
            'state' => $this->state,
        ];
        if ($this->trafficLight !== null) {
            $data['trafficLight'] = $this->trafficLight->serialize();
        }
        return $data;
    }

    public static function fromArray(array $data, $logger = null): self
    {
        $roads = [];
        $roads[] = Road::fromArray($data['verticalRoad'], $logger);
        $roads[] = Road::fromArray($data['horizontalRoad'], $logger);
        $object = new self(...$roads);
        $object->state = $data['state'];
        $object->trafficLight = array_key_exists('trafficLight', $data)
            ? TrafficLight::fromArray($data['trafficLight'], $logger)
            : null;
        return $object;
    }

    public function getRoads(): array
    {
        return [$this->verticalRoad, $this->horizontalRoad];
    }

    protected static function carHasPriority(Car $myCar, Car $otherCar): bool
    {
        $dominateDirectionConfig = [
            DirectedRectangle::DIRECTION_TOP => DirectedRectangle::DIRECTION_RIGHT,
            DirectedRectangle::DIRECTION_RIGHT => DirectedRectangle::DIRECTION_BOTTOM,
            DirectedRectangle::DIRECTION_BOTTOM => DirectedRectangle::DIRECTION_LEFT,
            DirectedRectangle::DIRECTION_LEFT => DirectedRectangle::DIRECTION_TOP,
        ];

        if (
            $myCar->getTransformation() === $otherCar->getTransformation()
            && $myCar->isReverse() !== $otherCar->isReverse()) {
            return
                $otherCar->getPlannedManeur() === PlannedManeur::LEFT //Другая машина поворачивает налево
                && $myCar->getPlannedManeur() !== PlannedManeur::LEFT; //И наша машина не поворачивает налево
        }

        if (!array_key_exists($myCar->getDirection(), $dominateDirectionConfig)) {
            return false;
        }

        return $otherCar->getDirection() === $dominateDirectionConfig[$myCar->getDirection()];
    }

    /**
     * @return TrafficLight|null
     */
    public function getTrafficLight(): ?TrafficLight
    {
        return $this->trafficLight;
    }

    /**
     * @param TrafficLight|null $trafficLight
     */
    public function setTrafficLight(?TrafficLight $trafficLight): void
    {
        $this->trafficLight = $trafficLight;
    }
}