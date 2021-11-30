<?php
namespace Src;

interface ArrayableInterface
{
    public function serialize(): array;

    public static function fromArray(array $data, $logger = null): self;
}