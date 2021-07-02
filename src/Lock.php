<?php

namespace Synchronized\Lock;

abstract class Lock
{
    public function mutex(callable $function){
        $this->acquire();
        try {
            $result = $function();
        }catch (\Exception $e){
            // todo
        } finally {
            $this->release();
        }
        return $result;
    }

    abstract protected function acquire();

    abstract protected function release();
}