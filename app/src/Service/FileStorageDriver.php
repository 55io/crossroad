<?php

namespace Src\Service;

class FileStorageDriver
{
    private $path;
    public function read()
    {
        $this->beforeOperation();
        $stream = fopen($this->path, 'r');
        $stringData = fread($stream, filesize($this->path));
        fclose($stream);

        try {
            return json_decode($stringData, true);
        } catch (\Throwable $e) {
            return $stringData;
        }
    }

    public function write($serializedData): bool
    {
        return $this->writeToFile($serializedData, 'w+');
    }

    public function reWrite($serializedData): bool
    {
        return $this->writeToFile($serializedData, 'w+');
    }

    public function clear(): bool
    {
        return $this->reWrite('');
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

    private function writeToFile($data, string $flag = 'a'): bool
    {
        $stream = fopen($this->path, $flag);
        if(!is_string($data)){
            $stringData = json_encode($data);
        } else {
            $stringData = $data;
        }
        fwrite($stream, $stringData);
        fclose($stream);
        return true;
    }

    private function beforeOperation()
    {
        if(!file_exists($this->path)) {
            $this->clear();
        }
    }
}