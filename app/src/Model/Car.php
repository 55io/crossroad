<?php
namespace Src\Model;

use Src\Dictionary\PlannedManeur;
use Src\Model\Event\CarChangedRoadEvent;
use Src\Model\Event\CarDrivingOnRoadEvent;
use Src\Model\Event\CarStoppedEvent;
use Src\Model\Geometry\DirectedRectangle;
use Src\Model\Geometry\Rectangle;
use Src\Service\Check\MaxSpeed;
use Src\Service\Check\PreviousCarDistance;
use Src\Service\FileLogger;
use Src\Service\SpeedCalculator;
use Src\Service\UseFileLoggerTrait;

final class Car extends DirectedRectangle
{
    use UseFileLoggerTrait;

    private $state = 'stop';

    /**
     * @var string
     */
    private $plannedManeur = 'front';

    /**
     * @var int
     */
    private $maxSpeed = 20;

    /**
     * @var float
     */
    private $acc = 0.5;

    /**
     * @var int
     */
    private $speed = 0;

    /**
     * @var Lane|null
     */
    private $lane;

    /**
     * @var int
     */
    private $positionOnLane = 0;

    public function drive($withoutCrossroadChecks = false) {
        $this->beforeDrive($withoutCrossroadChecks);
        $this->maneur();
        $this->afterDrive();
    }

    protected function maneur() {
        $oldLane = $this->lane;
        switch ($this->plannedManeur) {
            case PlannedManeur::LEFT:
                $leftLane = $this->lane->getAvailableLeftLane($this->getPositionOnLane());
                if($leftLane && $this->speed > 0) {
                    $this->lane->deleteCar($this);
                    /** @var Lane $leftLane */
                    $leftLane->addCar($this, $leftLane->getCrossRoadStartPosition() + $this->getLength());
                    $this->plannedManeur = 'front';
                    $event = new CarChangedRoadEvent($this, $oldLane);
                    $event->setManeur(PlannedManeur::LEFT);
                    break;
                }
            case PlannedManeur::RIGHT:
                $rightLane = $this->lane->getAvailableRightLane($this->getPositionOnLane());
                if($rightLane && $this->speed > 0) {
                    $this->lane->deleteCar($this);
                    /** @var Lane $rightLane */
                    $rightLane->addCar($this, $rightLane->getCrossRoadStartPosition() + $this->getLength());
                    $this->plannedManeur = 'front';
                    $event = new CarChangedRoadEvent($this, $oldLane);
                    $event->setManeur(PlannedManeur::RIGHT);
                    break;
                }
            default :
                $this->positionOnLane += $this->speed;
                $event = new CarDrivingOnRoadEvent($this, $oldLane);
        }
        $this->logEvent($event);
    }

    protected function beforeDrive($withoutCrossroadChecks = false) {
        if($this->needBraking($withoutCrossroadChecks)) {
            $this->brake();
            $this->setState('brake');
            return;
        }

        if($this->canAccelerate($withoutCrossroadChecks)) {
            $this->accelerate();
            $this->setState('accelerate');
            return;
        }

        $this->setState('driving');
    }

    protected function afterDrive() {
        if($this->speed === SpeedCalculator::DEFAULT && $this->state !== 'stop') {
            $this->setState('stop');
            $event = new CarStoppedEvent($this, $this->getLane());
            $this->logEvent($event);
        } else {
            $this->updatePointByPositionOnLane();
        }

        if ($this->getPositionOnLane() > $this->lane->length) {
            $this->lane->deleteCar($this);
            // TODO сюда unset прикрутить
        }
    }

    protected function updatePointByPositionOnLane() {
        $this->point = $this->lane->getPointByPosition($this->positionOnLane);
        if(!$this->isReverse()) {
            $directionAttribute = $this->getDirectionAttribute();
            $this->point->$directionAttribute += -$this->length;
        }
    }

    protected function accelerate() {
        $this->speed = SpeedCalculator::calculateForAcceleration($this->speed, $this->acc, $this->maxSpeed);
    }

    protected function brake() {
        $this->speed = SpeedCalculator::calculateForBraking($this->speed, $this->acc);
    }

    protected function needBraking($withoutCrossroadChecks = false): bool
    {
        $checks = self::getBrakeChecks();
        if(!$withoutCrossroadChecks) {
            $checks = array_merge($checks, self::getCrossroadChecks());
        }
        foreach ($checks as $check) {
            if($check::check($this, $this->lane) === false) {
                return true;
            }
        }

        return false;
    }

    protected function canAccelerate($withoutCrossroadChecks = false): bool
    {
        $checks = self::getAccelerateChecks();
        if(!$withoutCrossroadChecks) {
            $checks = array_merge($checks, self::getCrossroadChecks());
        }
        $nextSpeed = SpeedCalculator::calculateForAcceleration(
            $this->getSpeed(), $this->getAcc(), $this->lane->getMaxSpeed() ?? $this->getMaxSpeed());
        foreach ($checks as $check) {
            if($check::check($this, $this->lane, $nextSpeed) === false) {
                return false;
            }
        }

        return true;
    }

    protected static function getBrakeChecks() {
        return [
            PreviousCarDistance::class,
        ];
    }

    protected static function getAccelerateChecks() {
        return [
            MaxSpeed::class,
            PreviousCarDistance::class,
        ];
    }

    protected static function getCrossroadChecks() {
        return [
            \Src\Service\Check\CrossRoad::class,
        ];
    }

    public function serialize(): array
    {
        $result = parent::serialize();
        $result['state'] = $this->state;
        $result['maxSpeed'] = $this->maxSpeed;
        $result['acc'] = $this->acc;
        $result['speed'] = $this->speed;
        $result['positionOnLane'] = $this->positionOnLane;
        $result['brakeLength'] = SpeedCalculator::calculateBrakeLength($this->getSpeed(), $this->getAcc());
        $result['brakePosition'] = SpeedCalculator::calculateBrakeLength($this->getSpeed(), $this->getAcc()) + $this->positionOnLane;
        $result['needBraking'] = $this->needBraking();
        $result['canAccelerate'] = $this->canAccelerate();
        $result['plannedManeur'] = $this->plannedManeur;
        return $result;
    }

    /**
     * @param static $object
     * @param array $data
     * @throws \Exception
     */
    protected static function setAdditionalData(Rectangle $object, array $data, $logger = null): void
    {
        parent::setAdditionalData($object, $data);
        $object->state = $data['state'];
        $object->maxSpeed = $data['maxSpeed'];
        $object->acc = $data['acc'];
        $object->speed = $data['speed'];
        $object->positionOnLane = $data['positionOnLane'];
        $object->plannedManeur = $data['plannedManeur'];
        if($logger instanceof FileLogger) {
            $object->setLogger($logger);
        }
    }

    /**
     * @param int $positionOnLane
     */
    public function setPositionOnLane(int $positionOnLane): void
    {
        $this->positionOnLane = $positionOnLane;
        $this->updatePointByPositionOnLane();
    }

    // ###### Getters&Setters

    /**
     * @return float
     */
    public function getSpeed(): float
    {
        return $this->speed;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return Lane|null
     */
    public function getLane(): ?Lane
    {
        return $this->lane;
    }

    /**
     * @param Lane $lane
     */
    public function setLane(Lane $lane): void
    {
        $this->lane = $lane;
    }

    /**
     * @return int
     */
    public function getPositionOnLane(): int
    {
        return $this->positionOnLane;
    }

    /**
     * @param float $acc
     */
    public function setAcc(float $acc): void
    {
        $this->acc = $acc;
    }

    /**
     * @param int $speed
     */
    public function setSpeed(int $speed): void
    {
        $this->speed = $speed;
    }

    /**
     * @param int $maxSpeed
     */
    public function setMaxSpeed(int $maxSpeed): void
    {
        $this->maxSpeed = $maxSpeed;
    }

    /**
     * @return float
     */
    public function getAcc(): float
    {
        return $this->acc;
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
     * @return int
     */
    public function getMaxSpeed(): int
    {
        return $this->maxSpeed;
    }

    /**
     * @return string
     */
    public function getPlannedManeur(): string
    {
        return $this->plannedManeur;
    }

    /**
     * @param string $plannedManeur
     */
    public function setPlannedManeur(string $plannedManeur): void
    {
        $this->plannedManeur = $plannedManeur;
    }
}