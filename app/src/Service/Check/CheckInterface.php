<?php

namespace Src\Service\Check;

use Src\Model\Car;
use Src\Model\Lane;

interface CheckInterface
{
    /**
     * Проверяет безопасность движения со скоростью $speed/текущей скоростью
     */
    public static function check(Car $car, Lane $lane, int $speed = null): bool;
}