<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-4
 * Time: 下午4:07
 */

namespace App\Websocket;
use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;
use Message\PingMessage;
/**
 * 用来处理websocket　接受到客户端的数据之后　　继续的操作
 * Class Message
 * @package App\Websocket
 * @author chenlin
 * @date 2019/4/4
 */
class Message{
    /**
     * 构造函数需要进行传递的数据进行分析　来进行接下来的操作
     * Message constructor.
     * @param Server $server
     * @param Frame $frame
     * @return void
     * @method ping[心跳包检测] bind[用户映射] produce[生产数据] consume[消费数据]
     */
    public function __construct(Server $server,Frame $frame){
        //获取接受到的数据
        $data = $frame->data;
        $data = json_decode($data,true);
        switch ($data['method']){
            case 'ping' :
                $message = new PingMessage($server,$data['data'],$frame);
                break;
            case 'bind' :
                $message = new
        }
    }
}