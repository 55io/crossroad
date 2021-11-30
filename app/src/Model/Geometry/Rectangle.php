<?php

namespace Src\Model\Geometry;

use Src\ArrayableInterface;

class Rectangle implements ArrayableInterface
{
    public const TRANSFORMATION_VERTICAL = 'vertical';
    public const TRANSFORMATION_HORIZONTAL = 'horizontal';

    /**
     * top-left point
     * @var Point
     */
    public $point;

    /**
     * @var int Длина
     */
    public $length;

    /**
     * @var int Ширина
     */
    public $width;

    /**
     * @var string
     */
    public $transformation = self::TRANSFORMATION_HORIZONTAL;

    public function __construct(int $length, int $width)
    {
        $this->length = $length;
        $this->width = $width;
    }

    /**
     * @param Point $point
     */
    public function setPoint(Point $point): void
    {
        $this->point = $point;
    }

    public function getDirectionAttribute(): string
    {
        $directionAttrConfig = [
            self::TRANSFORMATION_VERTICAL => 'y',
            self::TRANSFORMATION_HORIZONTAL => 'x'
        ];

        return $directionAttrConfig[$this->transformation];
    }

    public function getNotDirectionAttribute(): string
    {
        $notDirectionAttrConfig = [
            self::TRANSFORMATION_VERTICAL => 'x',
            self::TRANSFORMATION_HORIZONTAL => 'y'
        ];

        return $notDirectionAttrConfig[$this->transformation];
    }

    public function isVertical(): bool
    {
        return $this->transformation === self::TRANSFORMATION_VERTICAL;
    }

    public function isHorizontal(): bool
    {
        return $this->transformation === self::TRANSFORMATION_HORIZONTAL;
    }

    public function serialize(): array
    {
        return [
            'point' => $this->point->serialize(),
            'transformation' => $this->transformation,
            'length' => $this->length,
            'width' => $this->width
        ];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data, $logger = null): self
    {
        $object = new static($data['length'], $data['width']);
        static::setAdditionalData($object, $data, $logger);
        return $object;
    }

    /**
     * @param static $object
     * @param array $data
     */
    protected static function setAdditionalData(Rectangle $object, array $data): void
    {
        $point = Point::fromArray($data['point']);
        $object->setPoint($point);
        $object->setTransformation($data['transformation']);
    }

    /**
     * @param string $transformation
     */
    public function setTransformation(string $transformation): void
    {
        $this->transformation = $transformation;
    }

    /**
     * @return string
     */
    public function getTransformation(): string
    {
        return $this->transformation;
    }
}