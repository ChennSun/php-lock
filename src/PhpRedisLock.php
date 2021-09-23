<?php

declare(strict_types=1);

namespace Synchronized\Lock;


/**
 * Class PhpRedisLock
 * php extension phpredis lock
 */
class PhpRedisLock extends RedisLock
{
    /**
     * set lock
     * @return bool
     */
    protected function setLock(): bool {

        if ($this->redisInstance->set(
            $this->key,
            $this->token,
            ['NX', 'PX' => $this->expire]
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