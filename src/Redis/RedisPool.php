<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/23
 * Time: 9:55
 */

namespace GoSwoole\BaseServer\Plugins\Redis;


use GoSwoole\BaseServer\Coroutine\Channel;
use Redis;

class RedisPool
{
    /**
     * @var Channel
     */
    protected $pool;
    /**
     * @var RedisConfig
     */
    protected $redisConfig;

    /**
     * RedisPool constructor.
     * @param RedisConfig $redisConfig
     * @throws \GoSwoole\BaseServer\Server\Exception\ConfigException
     */
    public function __construct(RedisConfig $redisConfig)
    {
        $this->redisConfig = $redisConfig;
        $redisConfig->buildConfig();
        $this->pool = new Channel($redisConfig->getPoolMaxNumber());
        for ($i = 0; $i < $redisConfig->getPoolMaxNumber(); $i++) {
            $db = new Redis();
            $this->pool->push($db);
        }
    }

    /**
     * @return Redis
     * @throws \GoSwoole\BaseServer\Exception
     */
    public function db(): Redis
    {
        $db = getContextValueByClassName(Redis::class);
        if ($db == null) {
            $db = $this->pool->pop();
           if($db instanceof Redis){
               if(!$db->isConnected()){
                   if(!$db->connect($this->redisConfig->getHost(),$this->redisConfig->getPort())){
                       throw new RedisException($db->getLastError());
                   }
                   if(!empty($this->redisConfig->getPassword())){
                       if(!$db->auth($this->redisConfig->getPassword())){
                           throw new RedisException($db->getLastError());
                       }
                   }
               }
               $db->select($this->redisConfig->getSelectDb());
           }
            defer(function () use ($db) {
                $this->pool->push($db);
            });
            setContextValue("redis", $db);
        }
        return $db;
    }

    /**
     * @return RedisConfig
     */
    public function getRedisConfig(): RedisConfig
    {
        return $this->redisConfig;
    }

    /**
     * @param RedisConfig $redisConfig
     */
    public function setRedisConfig(RedisConfig $redisConfig): void
    {
        $this->redisConfig = $redisConfig;
    }

}