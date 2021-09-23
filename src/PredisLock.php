<?php

/**
 *  PredisLock for Predis libraries
 */
class PredisLock extends RedisLock
{

    /**
     * set lock
     * @return bool
     */
    protected function setLock(): bool {
        if ($this->redisInstance->set(
            $this->key,
            $this->token,
            'PX',
            $this->expire,
            'NX'
        )) {
            return true;
        }
        return false;
    }

    /**
     * del lock
     * @return bool
     */
    protected function delLock(): bool {
        $script = "if redis.call('get',KEYS[1]) == ARGV[1]
        then
            return redis.call('del',KEYS[1])
        else 
            return 1
        end
        ";
        return $this->redisInstance->eval($script, 1 , $this->key, $this->token) ? true : false;
    }
}