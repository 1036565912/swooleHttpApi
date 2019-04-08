<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-4
 * Time: 下午5:02
 */

namespace Message;
use AbstractInterface\AbstractMessage;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
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
        //回收redis链接资源
        if(!RedisPool::getInstance()->recycleObj($redis)){
            //如果回收失败　需要进行日志操作
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']---redis pool recycle error'.PHP_EOL);
        }
        if($result){
            return $this->push();
        }else{
            return $this->push(false);
        }
        // TODO: Implement deal() method.
    }

    //@tip 这里应该加一个成功与否的标志
    public function push(bool $flag = true,$data = ''){
        // TODO: Implement push() method.
        $result = [
            'method' => 'bind',
            'result' => $data,
            'code'   => $flag ? self::SUCCESS_CODE : self::ERROR_CODE,
        ];
        return $this->server->push($this->frame->fd,json_encode($result));
    }
}