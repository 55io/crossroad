<?php

namespace Src\Model\Geometry;

final class Point
{
    /**
     * @var int
     */
    public $x;

    /**
     * @var int
     */
    public $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function serialize(): array
    {
        return ['x' => $this->x, 'y' => $this->y];
    }

    public static function fromArray(array $array): Point
    {

        return new self($array['x'], $array['y']);
    }
}