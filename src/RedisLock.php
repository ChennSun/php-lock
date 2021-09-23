<?php


use Synchronized\Lock\Lock;

use Synchronized\Lock\Exception\AcquireLockTimeoutException;

/**
 * redis lock base class
 * Class RedisLock
 */
abstract class RedisLock extends Lock
{
    /**
     * (milliseconds) loop interval, 500ms, Prevent CPU from soaring
     * 在锁被占用的情况下，控制循环请求锁的频率， 防止并发场景下造成redis服务器cpu的飙高。
     */
    const ACQUIRE_LOCK_LOOP_INTERVAL = 100;

    /**
     * @var object
     * php redis client instance
     */
    protected $redisInstance;

    /**
     * @var string
     * redis lock key
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
     * PhpRedisLock constructor.
     * @param object $redisInstance redis client instance
     * @param string $key redis lock key
     * @param int $timeout (milliseconds) time of trying to acquire lock, default is 1000 milliseconds
     * @param int $expire (milliseconds) lock expire time, default is 2000 milliseconds
     */
    public function __construct($redisInstance, string $key, int $timeout = 1000, int $expire = 2000)
    {
        $this->redisInstance = $redisInstance;
        $this->key = $key;
        $this->timeout = $timeout;
        $this->expire = $expire;
        $this->token = $this->generateToken();
    }

    /**
     * acquire lock
     * @return bool
     */
    protected function acquire(): bool {
        $remainTime = $this->timeout;
        do {
            if ($this->setLock()) {
                return true;
            }
            $sleepTime = min($remainTime, self::ACQUIRE_LOCK_LOOP_INTERVAL);
            usleep($sleepTime);
            $remainTime -= $sleepTime;
        } while ($remainTime);
        throw new AcquireLockTimeoutException();
    }

    /**
     * release lock
     * @return bool
     */
    protected function release(): bool {
        return $this->delLock();
    }

    /**
     * set lock api
     * @return bool
     */
    abstract protected function setLock() : bool;

    /**
     * del lock api
     * @return bool
     */
    abstract protected function delLock() : bool;

}