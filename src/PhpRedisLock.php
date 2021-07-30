<?php

declare(strict_types=1);

/**
 * Class PhpRedisLock
 * php extension phpredis lock
 */
class PhpRedisLock extends Lock
{
    /**
     * (milliseconds) loop interval, 500ms, Prevent CPU from soaring
     * 在锁被占用的情况下，控制循环请求锁的频率， 防止并发场景下获取锁造成redis 服务器cpu的飙高。
     */
    const ACQUIRE_LOCK_LOOP_INTERVAL = 100;

    /**
     * @var Redis
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
     * @param redis $redisInstance redis client instance
     * @param string $key redis lock key
     * @param int $timeout (milliseconds) time of trying to acquire lock, default is 1000 milliseconds
     * @param int $expire (milliseconds) lock expire time, default is 2000 milliseconds
     */
    public function __construct(redis $redisInstance, string $key, int $timeout = 1000, int $expire = 2000)
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
            if ($this->redisInstance->set(
                $this->key,
                $this->token,
                ['NX', 'PX' => $this->expire]
            )) {
                return true;
            }
            $sleepTime = min($remainTime, self::ACQUIRE_LOCK_LOOP_INTERVAL);
            usleep($sleepTime);
            $remainTime -= $sleepTime;
        } while ($remainTime);

        // TODO 抛异常会好点， bool存在歧义
        return false;
    }

    /**
     * release lock
     * @return bool
     */
    protected function release(): bool {
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