<?php
namespace Src\Model\Event;

interface EventInterface
{
    public static function getName(): string;

    public function getTargetData(): array;

    public function getLinkedObjectData(): ?array;
}