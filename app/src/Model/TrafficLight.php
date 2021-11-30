<?php
namespace Src\Model;

use Src\ArrayableInterface;
use Src\Model\Event\TrafficLightSwitchedEvent;
use Src\Model\Light\AbstractLight;
use Src\Model\Light\GreenLight;
use Src\Model\Light\RedLight;
use Src\Service\FileLogger;
use Src\Service\UseFileLoggerTrait;

final class TrafficLight implements ArrayableInterface
{
    use UseFileLoggerTrait;
    /**
     * @var AbstractLight
     */
    private $currentLight;

    private $duration = 200;

    public function __construct() {
        $this->currentLight = new RedLight();
    }

    public function switch()
    {
        $previousLight = $this->currentLight;
        $nextLight = $this->currentLight->getNext();
        $this->currentLight = new $nextLight();
        $event = new TrafficLightSwitchedEvent($this, $previousLight);
        $this->logEvent($event);
    }

    public function serialize(): array
    {
        return [
            'duration' => $this->duration,
            'currentLight' => $this->currentLight->serialize(),
            'logger' => $this->logger instanceof FileLogger
        ];
    }

    public static function fromArray(array $data, $logger = null): self
    {
        $object = new TrafficLight();
        $object->duration = $data['duration'];
        $object->currentLight = AbstractLight::instantiateByName($data['currentLight']['name']);
        if($logger instanceof FileLogger) {
            $object->setLogger($logger);
        }
        return $object;
    }

    public function canVerticalRoadDrive(): bool
    {
        return $this->currentLight->canVerticalRoadDrive();
    }
    public function canHorizontalRoadDrive(): bool
    {
        return $this->currentLight->canHorizontalRoadDrive();
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }
}