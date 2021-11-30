<?php

namespace Src\Model;

use Src\Model\Geometry\Rectangle;

final class Road extends Rectangle
{
    /**
     * @var bool
     */
    public $hasPriority = false;

    /**
     * @var Lane[]
     */
    private $laneCollection = [];

    /** @var null|CrossRoad  */
    private $crossRoad = null;

    /**
     * Добавляет полосу слева направо, сверху вниз
     *
     * @param Lane $lane
     */
    public function addLane(Lane $lane)
    {
        $lastLane = end($this->laneCollection);
        if($lastLane) {
            $notDirectionAttr = $this->getNotDirectionAttribute();
            $point = clone($lastLane->point);
            $point->$notDirectionAttr += $lastLane->width;
        } else {
            $point= clone($this->point);
        }

        $lane->setPoint($point);
        $lane->setRoad($this);
        $lane->setTransformation($this->transformation);
        $this->laneCollection[] = $lane;
    }

    /**
     * @return Lane[]
     */
    public function getLaneCollection(): array
    {
        return $this->laneCollection;
    }

    public function serialize(): array
    {
        $result = parent::serialize();
        $result['hasPriority'] = $this->hasPriority;
        $result['lanes'] = $this->serializeLanes();
        return $result;
    }

    /**
     * @param static $object
     * @param array $data
     */
    protected static function setAdditionalData(Rectangle $object, array $data, $logger = null): void
    {
        parent::setAdditionalData($object, $data);
        $object->hasPriority = $data['hasPriority'];
        foreach ($data['lanes'] as $laneData) {
            $lane = Lane::fromArray($laneData, $logger);
            $lane->setRoad($object);
            $object->laneCollection[] = $lane;
        }
    }

    private function serializeLanes(): array
    {
        $result = [];
        foreach ($this->laneCollection as $lane) {
            $result[] = $lane->serialize();
        }
        return $result;
    }

    /**
     * @return CrossRoad|null
     */
    public function getCrossRoad(): ?CrossRoad
    {
        return $this->crossRoad;
    }

    /**
     * @param CrossRoad|null $crossRoad
     */
    public function setCrossRoad(?CrossRoad $crossRoad): void
    {
        $this->crossRoad = $crossRoad;
    }
}