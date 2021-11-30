<?php

namespace Src\Service;

use Src\Model\Event\EventInterface;

trait UseFileLoggerTrait
{
    /**
     * @var FileLogger|null
     */
    protected $logger;

    public function logEvent(EventInterface $event)
    {
        if($this->logger) {
            $this->logger->log($event);
        }
    }

    /**
     * @param FileLogger $logger
     */
    public function setLogger(FileLogger $logger): void
    {
        $this->logger = $logger;
    }
}