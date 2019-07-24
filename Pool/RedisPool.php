<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-3-29
 * Time: 上午11:48
 */
namespace Pool;
use Swoole\Coroutine\Channel;
use Component\SingleTon;
use UserException\RedisException;
use AbstractInterface\AbstractPool;
use Swoole\Coroutine\Redis;

/**
 * redis连接池　
 * swoole4.０版本以上　用协程代替了异步回调　　防止并发的时候　同个连接调用同一个连接　这里引入连接池
 * @author chenlin
 * @date 2019/3/29
 */
class RedisPool implements AbstractPool {
    use SingleTon;

    private static $instance;
    private $redisPool; //连接池
    private $currentCount = 0; //当前连接池链接的数量
    private $maxCount; //当前连接池最大的连接数量
    private $host ;
    private $port;
    private $timeOut;
    /**
     * 构造函数
     * RedisPool constructor.
     * @param int $minNum 用于热启动  在子进程初始化的时候就在连接池中创建一部分连接　防止高并发导致进程堵塞
     * @author chenlin
     * @date 2019
     */
    protected  function __construct(int $minNum){
        //redis连接资源的配置
        $redis_config = config('redis');
        $this->host = $redis_config['host'];
        $this->port  = $redis_config['port'];
        $this->timeOut = $redis_config['timeOut'];
        $this->maxCount = config('pool.maxCount');
        //创建协程通道
        $this->redisPool = new Channel($this->maxCount);
        //开始实例化热启动需要的连接数
        for($i = 0;$i<$minNum;$i++){
            $redis = new \Redis();    //@tip　　swoole官方推荐使用enableCoroutine + phpredis  or  predis
            $result = $redis->connect($this->host,$this->port,$this->timeOut);
            if(!$result){
                throw new RedisException('coroutine redis initialize error!');
            }
            $this->redisPool->push($redis);
            $this->currentCount++;
        }
    }

    /**
     * 获取一个连接对象
     * @author chenlin
     * @date 2019/3/29
     * @return \Swoole\Coroutine\Redis | mixed
     * @tip 这里需要做一个检查　判断当前连接池是否还有连接　　如果没有则需要进行严格的逻辑操作
     * @tip 这里需要进行异常捕捉　
     */
    public function getObj(){
       if($this->redisPool->length()){
           //代表还有数据
           return $this->redisPool->pop();
       }

       //如果不存在 则需要根据目前的状态进行相应的逻辑
        if($this->maxCount == $this->currentCount){
           //代表当前连接池已经饱和　　无法在调用了
            return false;
        }else if($this->maxCount > $this->currentCount){
            //代表还可以创建redis连接
            $redis = new \Redis();
            if(!$redis->connect($this->host,$this->port,$this->timeOut)){
                //创建失败　这里抛出异常　
                throw new RedisException('新建redis连接失败!');
            }
            //当前资源数目加一
            $this->currentCount++;
            return $redis;
        }
    }

    /**
     * 资源回收
     * @tip 记住协程结束之前一定要进行资源回收
     * @param Swoole\Coroutine\Redis;
     * @return bool
     * @author chenlin
     * @date 2019/3/29
     * @param $obj
     */
    public function recycleObj($obj){
        // TODO: Implement recycleObj() method.
        if($this->redisPool->push($obj)){
            return true;
        }
        //如果回收失败 需要进行连接池的连接数目的减少
        $this->currentCount--;
        return false;
    }
}