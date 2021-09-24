<?php

declare(strict_types=1);

namespace Synchronized\Lock;

use Synchronized\Lock\Exception\AcquireLockTimeoutException;

class MemcacheLock extends Lock
{
    /**
     * (milliseconds) loop interval, 500ms, Prevent CPU from soaring
     * 在锁被占用的情况下，控制循环请求锁的频率， 防止并发场景下造成服务器cpu的飙高。
     */
    const ACQUIRE_LOCK_LOOP_INTERVAL = 100;

    /**
     * @var \Memcache
     * php memcache client instance
     */
    protected $memcacheInstance;

    /**
     * @var string
     * memcache lock key
     */
    protected $key;

    /**
     * @var int
     * (milliseconds) time try to acquire lock, second
     */
    protected $timeout;

    /**
     * @var int
     * (milliseconds) lock expire time
     */
    protected $expire;

    /**
     * @var string
     * unique lock value
     */
    protected $token;

    /**
     * @param \Memcache $memcacheInstance memcache client instance
     * @param string $key memcache lock key
     * @param int $timeout (milliseconds) time of trying to acquire lock, default is 1000 milliseconds
     * @param int $expire (milliseconds) lock expire time, default is 2000 milliseconds
     */
    public function __construct(\Memcache $memcacheInstance, string $key, int $timeout = 1000, int $expire = 2000)
    {
        $this->memcacheInstance = $memcacheInstance;
        $this->key = $key;
        $this->timeout = $timeout;
        $this->expire = $expire;
        $this->token = $this->generateToken();
    }

    protected function acquire(): bool {
        $remainTime = $this->timeout;
        do {
            if ($this->memcacheInstance->add(
                $this->key,
                $this->token,
                $this->expire
            )) {
                return true;
            }
            $sleepTime = min($remainTime, self::ACQUIRE_LOCK_LOOP_INTERVAL);
            usleep($sleepTime);
            $remainTime -= $sleepTime;
        } while ($remainTime);
        throw new AcquireLockTimeoutException();
    }

    protected function release(): bool {
        return $this->memcacheInstance->delete($this->key) ? true : false;
    }
}