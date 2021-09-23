<?php

declare(strict_types=1);

namespace Synchronized\Lock;

abstract class Lock
{
    /**
     * mutex
     * @param callable $function
     * @return mixed
     */
    public function mutex(callable $function){
        $this->acquire();
        try {
            $result = $function();
        }catch (\Exception $e){
            throw $e;
        } finally {
            $this->release();
        }
        return $result;
    }

    /**
     * generate lock unique value
     * @return string
     */
    final protected function generateToken(): string {
        return uniqid();
    }

    abstract protected function acquire(): bool;

    abstract protected function release(): bool;
}