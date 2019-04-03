<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-2
 * Time: 下午4:31
 */

namespace Helper;
use Component\SingleTon;
use Swoole\WebSocket\Server;
/** 用来保存当前的全局server对象　@author:chenlin @date:2019/4/2 */
class ServerManager{
    use SingleTon;
    protected  $swoole_server;

    /**
     * 存储全局server对象
     * @param Server $server
     */
    public function setSwooleServer(Server $server){
        $this->swoole_server = $server;
    }

    public function getSwooleServer(){
        return $this->swoole_server;
    }
}