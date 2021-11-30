<?php

namespace Src\Service;

use Src\Model\Event\EventInterface;

class FileLogger
{
    /**
     * @var FileStorageDriver
     */
    private $storage;

    /**
     * @var array
     */
    private $currentLog = [];

    public function __construct(string $logPath)
    {
        $this->storage = new FileStorageDriver();
        $this->storage->setPath($logPath);
    }

    public function log(EventInterface $event)
    {
        $this->currentLog[] = json_encode([
            'eventName' => $event::getName(),
            'eventObject' => $event->getTargetData(),
            'linkedObjectsData' => $event->getLinkedObjectData()
        ], JSON_PRETTY_PRINT);
    }

    public function writeLog()
    {
        $separator = "\r\n";
        $this->storage->write($separator . implode($separator, $this->currentLog));
    }
}