<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-4
 * Time: 下午4:34
 */
namespace Message;
use AbstractInterface\AbstractMessage;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * 心跳包检测消息处理
 * @author chenlin
 * @date 2019/4/4
 */


class PingMessage extends AbstractMessage{
    /**
     * @param Server $server
     * @param $data 客户端传递过来的数据[可能没有]
     * @param Frame $frame
     * @return mixed
     */
    public function deal(){
        /** 没有任务需要处理　直接返回数据 */
        return $this->push();
    }

    /**
     * @param string $data  [需要发送给客户端的数据]
     * @return mixed
     */
    public function push($data = ''){
        // TODO: Implement push() method.
        $response_info = [
            'method' => 'ping',
            'result' => $data,
            'code'   => self::SUCCESS_CODE
        ];
        return $this->server->push($this->frame,json_encode($response_info));
    }
}