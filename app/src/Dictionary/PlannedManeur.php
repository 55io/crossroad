<?php
namespace Src\Dictionary;

class PlannedManeur
{
    public const LEFT = 'left';
    public const RIGHT = 'right';
    public const FRONT = 'front';

    public static function getRandom(): string
    {
        $availableValues = [
            self::LEFT,
            self::RIGHT,
            self::FRONT,
        ];

        return $availableValues[array_rand($availableValues)];
    }
}