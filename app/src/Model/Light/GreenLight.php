<?php
namespace Src\Model\Light;

final class GreenLight extends AbstractLight
{

    public function getNext(): string
    {
        return RedLight::class;
    }

    public function getName(): string
    {
        return 'green';
    }

    public function getVerticalRoadColor(): string
    {
        return 'green';
    }

    public function getHorizontalRoadColor(): string
    {
        return 'red';
    }

    public function canVerticalRoadDrive(): bool
    {
        return true;
    }
    public function canHorizontalRoadDrive(): bool
    {
        return false;
    }
}