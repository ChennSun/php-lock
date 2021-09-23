<?php

use Synchronized\Lock\PhpRedisLock;

$key = "test:ok";
$redisInstance = Redis::connection();
$result = (new PhpRedisLock($redisInstance, $key))->mutex(function (){
    echo 'ok';
});

return $result;