<?php

namespace Src;

use Src\Dictionary\PlannedManeur;
use Src\Model\Car;
use Src\Model\CrossRoad;
use Src\Model\Geometry\DirectedRectangle;
use Src\Model\Geometry\Point;
use Src\Model\Geometry\Rectangle;
use Src\Model\Lane;
use Src\Model\Road;
use Src\Model\TrafficLight;
use Src\Service\FileLogger;
use Src\Service\FileStorageDriver;

class Application
{
    private const BASE_STORAGE_PATH = 'data/buffer.json';
    private const LOG_FILE_PATH = 'data/log';

    private $currentStep = 0;

    private $carMaxSpeed = 20;

    /**
     * @var null|CrossRoad
     */
    private $crossRoad = null;

    private $storage = null;

    /**
     * @var FileLogger
     */
    protected $logger = null;

    public function __construct(array $config = [])
    {
        if (!array_key_exists('storage', $config)) {
            $this->storage = new FileStorageDriver();
            $this->storage->setPath(self::BASE_STORAGE_PATH);
        }
        $this->logger = new FileLogger(self::LOG_FILE_PATH);
        $this->loadData();
    }

    public function run()
    {
        if ($this->currentStep % $this->crossRoad->getTrafficLight()->getDuration() === 0) {
            $this->crossRoad->getTrafficLight()->switch();
        }
        foreach ($this->crossRoad->getRoads() as $road) {
            /** @var Road $road */
            foreach ($road->getLaneCollection() as $lane) {
                if ($this->currentStep % $lane->getTrafficIntensity() === 0) {
                    $this->addNewCarToLine($lane);
                }
            }
        }
        $this->crossRoad->run();
        $this->currentStep++;
        $this->saveData();
        $this->logger->writeLog();
    }

    private function saveData()
    {
        $this->storage->reWrite($this->serializeData());
    }

    public function serializeData(): array
    {
        $result = [];
        $result['crossRoad'] = $this->crossRoad->serialize();
        $result['currentStep'] = $this->currentStep;
        $result['carMaxSpeed'] = $this->carMaxSpeed;
        return $result;
    }

    private function loadData()
    {
        $parsedData = $this->storage->read();
        if (empty($parsedData)) {
            $this->initDefault();
            $this->saveData();
            return;
        }
        $this->crossRoad = CrossRoad::fromArray($parsedData['crossRoad'], $this->logger);
        $this->currentStep = $parsedData['currentStep'];
        $this->carMaxSpeed = $parsedData['carMaxSpeed'] ?? 20;
    }

    private function initDefault()
    {
        $verticalLaneCount = 2;
        $verticalLaneConfig = [
            ['direction' => DirectedRectangle::DIRECTION_BOTTOM],
            ['direction' => DirectedRectangle::DIRECTION_TOP],
        ];

        $horizontalLaneCount = 2;
        $horizontalLaneConfig = [
            ['direction' => DirectedRectangle::DIRECTION_LEFT],
            ['direction' => DirectedRectangle::DIRECTION_RIGHT],
        ];

        $verticalRoad = new Road(1000, $verticalLaneCount * 10);
        $verticalRoad->hasPriority = true;
        $verticalRoad->setTransformation(Rectangle::TRANSFORMATION_VERTICAL);
        $verticalRoad->setPoint(new Point(500, 0));

        $horizontalRoad = new Road(1000, $horizontalLaneCount * 10);
        $horizontalRoad->setPoint(new Point(0, 500));

        for ($i = 0; $i < $verticalLaneCount; $i++) {
            $lane = new Lane(1000);
            $verticalRoad->addLane($lane);
            $lane->setDirection($verticalLaneConfig[$i]['direction']);
            $lane->setLogger($this->logger);
        }

        for ($i = 0; $i < $horizontalLaneCount; $i++) {
            $lane = new Lane(1000);
            $horizontalRoad->addLane($lane);
            $lane->setDirection($horizontalLaneConfig[$i]['direction']);
            $lane->setLogger($this->logger);
        }

        $this->crossRoad = new CrossRoad($horizontalRoad, $verticalRoad);
        $trafficLight = new TrafficLight();
        $trafficLight->setLogger($this->logger);
        $this->crossRoad->setTrafficLight($trafficLight);
    }

    private function addNewCarToLine(Lane $lane)
    {
        $car = new Car(20, 10);
        $car->setSpeed(0);
        $car->setAcc(0.5);
        $car->setPlannedManeur(PlannedManeur::getRandom());
        $car->setMaxSpeed($this->carMaxSpeed);
        $lane->addCar($car);
        $car->setLogger($this->logger);
    }

    /**
     * @param int $carMaxSpeed
     */
    public function setCarMaxSpeed(int $carMaxSpeed): void
    {
        $this->carMaxSpeed = $carMaxSpeed;
    }

    public function setSettings(?array $data)
    {
        $crossRoad = $this->crossRoad;

        if(!array_key_exists('hasCrosslight', $data)) {
            $crossRoad->setTrafficLight(null);
        } else {
            $trafficLight = $crossRoad->getTrafficLight() ?? new TrafficLight();
            if(array_key_exists('crosslightDuration', $data) && $this->validatePositiveIntegerValue($data['crosslightDuration'])) {
                $trafficLight->setDuration((int)$data['crosslightDuration']);
            }
            $crossRoad->setTrafficLight($trafficLight);
        }

        if(array_key_exists('priority', $data)) {
            $crossRoad->getVerticalRoad()->hasPriority = $data['priority'] == Rectangle::TRANSFORMATION_VERTICAL;
            $crossRoad->getHorizontalRoad()->hasPriority = $data['priority'] == Rectangle::TRANSFORMATION_HORIZONTAL;
        }

        if(array_key_exists('verticalLaneMaxSpeed', $data) && $this->validatePositiveIntegerValue($data['verticalLaneMaxSpeed'])) {
            foreach ($crossRoad->getVerticalRoad()->getLaneCollection() as $lane) {
                /** Lane */
                $lane->setMaxSpeed((int)$data['verticalLaneMaxSpeed']);
            }
        }

        if(array_key_exists('horizontalLaneMaxSpeed', $data) && $this->validatePositiveIntegerValue($data['horizontalLaneMaxSpeed'])) {
            foreach ($crossRoad->getHorizontalRoad()->getLaneCollection() as $lane) {
                /** Lane */
                $lane->setMaxSpeed((int)$data['horizontalLaneMaxSpeed']);
            }
        }

        if(array_key_exists('verticalLaneTrafficIntensity', $data) && $this->validatePositiveIntegerValue($data['verticalLaneTrafficIntensity'])) {
            foreach ($crossRoad->getVerticalRoad()->getLaneCollection() as $lane) {
                /** Lane */
                $lane->setTrafficIntensity((int)$data['verticalLaneTrafficIntensity']);
            }
        }

        if(array_key_exists('horizontalLaneTrafficIntensity', $data) && $this->validatePositiveIntegerValue($data['horizontalLaneTrafficIntensity'])) {
            foreach ($crossRoad->getHorizontalRoad()->getLaneCollection() as $lane) {
                /** Lane */
                $lane->setTrafficIntensity((int)$data['horizontalLaneTrafficIntensity']);
            }
        }

        if(array_key_exists('carMaxSpeed', $data) && $this->validatePositiveIntegerValue($data['carMaxSpeed'])) {
            $this->setCarMaxSpeed((int)$data['carMaxSpeed']);
        }
        $this->saveData();
    }

    private function validatePositiveIntegerValue($val): bool
    {
        return (int)$val > 0;
    }
}