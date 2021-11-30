<?php

namespace Src\Model\Event;

use Src\Model\Light\AbstractLight;
use Src\Model\TrafficLight;

class TrafficLightSwitchedEvent implements EventInterface
{
    /**
     * @var TrafficLight
     */
    protected $trafficLight;

    /**
     * @var AbstractLight
     */
    protected $previousLight;


    public function __construct(TrafficLight $trafficLight, AbstractLight $previousLight)
    {
        $this->trafficLight = $trafficLight;
        $this->previousLight = $previousLight;
    }

    public static function getName(): string
    {
        return 'Traffic light switched';
    }

    public function getTargetData(): array
    {
        return [
            'stateDuration' => $this->trafficLight->getDuration(),
            'currentState' => [
                'canVerticalRoadDrive' => $this->trafficLight->canVerticalRoadDrive(),
                'canHorizontalRoadDrive' => $this->trafficLight->canHorizontalRoadDrive()
            ],
            'previousState' => [
                'canVerticalRoadDrive' => $this->previousLight->canVerticalRoadDrive(),
                'canHorizontalRoadDrive' => $this->previousLight->canHorizontalRoadDrive()
            ]
        ];
    }

    public function getLinkedObjectData(): ?array
    {
        return null;
    }
}