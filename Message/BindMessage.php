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
use
/**
 * 信息映射消息处理
 * Class BindMessage
 * @package Message
 * @author chenlin
 * @date 2019/4/4
 */
class BindMessage extends AbstractMessage{
    public function deal(){
        //获取redis实例
        $redis = RedisPool::getInstance()->getObj();
        $redis->select(BIND_DATABASE);
        //进行用户信息与fd的绑定
        $result = $redis->set($this->data,$this->frame->fd);
        if($result){
            $this->push();
        }
        // TODO: Implement deal() method.
    }

    //@tip 这里应该加一个成功与否的标志
    public function push($data = ''){
        // TODO: Implement push() method.
        $result = [
            'method' => 'bind',
            'result' => $data,
            'code'   => self::SUCCESS_CODE
        ];
    }
}