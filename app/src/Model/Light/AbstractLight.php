<?php

namespace Src\Model\Light;

abstract class AbstractLight
{
    abstract public function getNext(): string;

    abstract public function getName(): string;

    abstract public function getVerticalRoadColor(): string;

    abstract public function getHorizontalRoadColor(): string;

    abstract public function canVerticalRoadDrive(): bool;

    abstract public function canHorizontalRoadDrive(): bool;

    public function serialize(): array
    {
        return [
            'name' => $this->getName(),
            'verticalRoadColor' => $this->getVerticalRoadColor(),
            'horizontalRoadColor' => $this->getHorizontalRoadColor()
        ];
    }

    public static function instantiateByName(string $name): self
    {
        //todo переписать на reflection?
        $config = [
            'green' => GreenLight::class,
            'red' => RedLight::class
        ];

        return new $config[$name]();
    }
}