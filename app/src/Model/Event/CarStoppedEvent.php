<?php

namespace Src\Model\Event;

final class CarStoppedEvent extends AbstractCarEvent
{
    public static function getName(): string
    {
        return 'Car stopped';
    }

    public function getTargetData(): array
    {
        return $this->getCarData();
    }

    public function getLinkedObjectData(): ?array
    {
        return null;
    }
}