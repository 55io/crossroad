<?php

namespace Src\Model\Geometry;

class DirectedRectangle extends Rectangle
{
    public const DIRECTION_LEFT = 'left';
    public const DIRECTION_RIGHT = 'right';
    public const DIRECTION_TOP = 'top';
    public const DIRECTION_BOTTOM = 'bottom';

    /**
     * @var string
     */
    public $direction = self::DIRECTION_RIGHT;

    public function isReverse(): bool
    {
        return $this->direction === self::DIRECTION_LEFT || $this->direction === self::DIRECTION_TOP;
    }

    /**
     * @param string $direction
     */
    public function setDirection(string $direction): void
    {
        if($this->canSetDirection($direction)) {
            $this->direction = $direction;
        } else {
            throw new \Exception('Wrong direction');
        }
    }

    private function canSetDirection(string $direction): bool
    {
        $directionConfig = [
            self::TRANSFORMATION_HORIZONTAL => [
                self::DIRECTION_RIGHT, self::DIRECTION_LEFT
            ],
            self::TRANSFORMATION_VERTICAL => [
                self::DIRECTION_BOTTOM, self::DIRECTION_TOP
            ],
        ];
        return in_array($direction, $directionConfig[$this->transformation]);
    }

    public function serialize(): array
    {
        $result = parent::serialize();
        $result['direction'] = $this->direction;
        return $result;
    }

    /**
     * @param static $object
     * @param array $data
     * @throws \Exception
     */
    protected static function setAdditionalData(Rectangle $object, array $data): void
    {
        parent::setAdditionalData($object, $data);
        $object->setDirection($data['direction']);
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }
}