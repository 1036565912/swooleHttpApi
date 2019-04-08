<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-4
 * Time: 下午4:23
 */

namespace AbstractInterface;
use Swoole\WebSocket\Server;
use Swoole\WebSocket\Frame;
/** 消息处理统一接口 @author:chenlin @date:2019/4/4 */
abstract  class AbstractMessage{
    const SUCCESS_CODE = 200;
    const ERROR_CODE = 500;
    protected $server;
    protected $data;
    protected $frame;
    public function __construct(Server $server,$data,Frame $frame){
        $this->server = $server;
        $this->data = $data;
        $this->frame = $frame;
    }
    abstract  public function deal(); //消息处理操作
    abstract  public function push(bool $flag = true,$data = ''); //消息返回
}