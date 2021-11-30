<?php
namespace Src\Model\Light;

final class RedLight extends AbstractLight
{

    public function getNext(): string
    {
        return GreenLight::class;
    }

    public function getName(): string
    {
        return 'red';
    }

    public function getVerticalRoadColor(): string
    {
        return 'red';
    }

    public function getHorizontalRoadColor(): string
    {
        return 'green';
    }

    public function canVerticalRoadDrive(): bool
    {
        return false;
    }
    public function canHorizontalRoadDrive(): bool
    {
        return true;
    }
}