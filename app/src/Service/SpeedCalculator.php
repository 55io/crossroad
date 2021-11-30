<?php
namespace Src\Service;

class SpeedCalculator
{
    public const DEFAULT = 0;
    public const MIN = 1;

    /**
     * Считает следующую скорость для ускорения
     *
     * @param int $speed
     * @param float $acc
     * @return int
     */
    public static function calculateForAcceleration(int $speed, float $acc, float $max): int
    {
        $newSpeed = self::calculate($speed, $acc);

        if($newSpeed < self::MIN) {
            return self::MIN;
        }

        if($newSpeed > $max) {
            return $max;
        }

        return round($newSpeed);
    }

    /**
     * Считает следующую скорость для торможения
     *
     * @param int $speed
     * @param float $acc
     * @return int
     */
    public static function calculateForBraking(int $speed, float $acc): int
    {
        $newSpeed = self::calculate($speed, -$acc);
        if($newSpeed < self::MIN) {
            return self::DEFAULT;
        }

        return round($newSpeed);
    }

    /**
     * Считает тормозной путь S=v^2/2a
     *
     * @param int $speed
     * @param float $acc
     * @return int
     */
    public static function calculateBrakeLength(int $speed, float $acc): int
    {
        return ($speed*$speed) / ($acc * 2);
    }

    private static function calculate(float $speed = self::DEFAULT, float $acceleration = self::DEFAULT)
    {
        return ($speed + $speed * $acceleration);
    }
}