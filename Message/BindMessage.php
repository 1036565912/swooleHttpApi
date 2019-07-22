<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-4
 * Time: 下午5:02
 */

namespace Message;
use AbstractInterface\AbstractMessage;
use Pool\RedisPool;
use Helper\Log;
use UserException\RedisException;

/**
 * 信息映射消息处理
 * Class BindMessage
 * @package Message
 * @author chenlin
 * @date 2019/4/4
 */
class BindMessage extends AbstractMessage{
    public function deal(){
        //获取redis实例 由于初始化的时候　可能抛出异常
        try {
            $redis = RedisPool::getInstance()->getObj();
        }catch (RedisException $e){
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return $this->push(false);
        }
        $redis->select(BIND_DATABASE);
        //进行用户信息与fd的绑定
        var_dump($this->data);
        var_dump($this->frame->fd);
        $result = $redis->set($this->data,$this->frame->fd);
        //并且需要存入反转数据库
        $redis->select(REFLECTION_DATABASE);
        $redis->set($this->frame->fd,$this->data);

        //进行历史消息的推送
        $this->historyPush($redis,$this->data);

        //资源回收
         RedisPool::getInstance()->recycleObj($redis);

        if($result){
            return $this->push();
        }else{
            return $this->push(false);
        }
    }

    //@tip 这里应该加一个成功与否的标志
    public function push(bool $flag = true,$data = ''){
        // TODO: Implement push() method.
        //@tip 由于存在问题
        $result = [
            'method' => 'bind',
            'result' => $data,
            'code'   => $flag ? self::SUCCESS_CODE : self::ERROR_CODE,
        ];
        return $this->server->push($this->frame->fd,json_encode($result));
    }

    /**
     * 历史消息推送
     * @param \Redis $redis　redis连接资源
     * @param string $user_id 建立映射关系的用户id
     * @return bool
     * @author chenlin
     * @date 2019/7/22
     */
    public function historyPush(\Redis $redis,string $user_id)
    {
        //选择历史数据库
        $redis->select(HISTORY_DATABASE);
        $length = $redis->lLen($user_id);
        for ($i=1; $i<=$length; $i++) {
            $info = $redis->lPop($user_id);
            $this->server->push($this->frame->fd,$info);
        }
        return true;
    }
}