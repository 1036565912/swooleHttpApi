<?php
namespace Server;

use Pool\MysqlPool;
use UserException\MysqlException;
use UserException\RedisException;
use Pool\RedisPool;
use Swoole\Runtime;
use Helper\Log;
use Component\Reflection;
use UserException\ParamValidException;
use Helper\Config;
use Helper\ServerManager;
use App\Websocket\Message;
/**
 * websocket类
 * @tip 同时注册了http请求回调和message回调　如果需要新的需求　则需要自己修改websocket类
 * @author chenlin
 * @date 2019/3/29
 */
class Websocket{
    private $_host;
    private $_port;
    protected $ws=null;
    public function __construct($host,$port){
        $this->_host = $host;
        $this->_port = $port;
        $this->ws = new \swoole_websocket_server($this->_host,$this->_port);
        $this->ws->set([
            'worker_num' => 1, //这里一般是内核的4倍到8倍  暂定
            'task_worker_num' => 4, //暂定
            'enable_coroutine' => true, //允许在各种回调函数调用之间　创建一个协程
            'task_enable_coroutine' => false, //这里task进程还是设置为同步阻塞的　使用php原声的阻塞函数
         ]);
        //实例化一个配置文件加载类 @date 2019/4/1 用来提高配置文件读取速度
        Config::getInstance();
        //添加当前的server对象到全局
        ServerManager::getInstance()->setSwooleServer($this->ws);
        //绑定回调
        $this->ws->on('open',[$this,'open']);
        $this->ws->on('message',[$this,'message']);
        $this->ws->on('WorkerStart',[$this,'workerStart']);
        $this->ws->on('request',[$this,'request']);
        $this->ws->on('task',[$this,'task']);
        $this->ws->on('finish',[$this,'finish']);
        $this->ws->on('close',[$this,'close']);
        //给一个提示　用来通知开发者　监听的ip和端口
        echo '服务器正在启动,IP为:'.$this->_host.',监听的端口为:'.$this->_port.PHP_EOL;
        $this->ws->start();
    }

    //服务启动时候加载的回调方法(主要用来加载配置信息)
    public function workerStart($server,$worker_id){
        //需要在worker进程添加一个协程redis连接池 协程mysql连接池
        if($server->taskworker === false){
            //由于可能在worker进程使用一些原生阻塞函数　这里需要开启swoole提供的一键协程化
            Runtime::enableCoroutine(true);
            //由于task进程是同步阻塞　无法使用协程redis客户端　所以这里只在worker进程中进行redis的热加载
            try{
                RedisPool::getInstance(config('pool.minCount'));
            }catch (RedisException $e){
                Log::getInstance()->error('[date:'.date('Y-m-d H:i:s',time()).'----error info:'.$e->getMessage().']'.PHP_EOL);
                var_dump($e->getMessage());
                return ;
            }

            try{
                MysqlPool::getInstance(config('pool.minCount'));
            }catch (MysqlException $e){
                Log::getInstance()->error('[date:'.date('Y-m-d H:i:s',time()).'----error info:'.$e->getMessage().']'.PHP_EOL);
                var_dump($e->getMessage());
                return ;
            }
        }

        echo "当前worker进程:{$worker_id}初始化成功!".PHP_EOL;
    }

    /**
     *处理http请求的回调
     *@param $method 请求的方法
     *@param $data json
     *@return json
     * @tip 这里有一个问题 就是google浏览器会请求两次 会请求一个icon文件
     */
    public function request($request,$response){
        //进行favicon.ico文件的过滤
        if(!refuseIconv($request,$response)){
            $response->status(404);
            return $response->end();
        }
        //v1.0版本　使用最简单的 s 兼容模式
        $result = $request->get['s'];
        if(empty($result)){
            $result[0]  = 'Index';//module
            $result[1]  = 'Index'; //controller
            $result[2]  = 'index'; //action
        }else{
            $result = explode('/',$result);
        }
        //注意当前module不支持多个单词  把首字母大写
        $result[0] = ucwords($result[0]);
        //注意当前controller不支持多个单词 把首字母大写
        $result[1] = ucwords($result[1]);

        //解析正常 走这里 由于v0.1版本不会添加视图层操作　这里只做api操作　返回类型就是json
        $response->header('Content-type','text/json;charset=utf-8');
        //进行方法操作的映射 这里可能抛出异常 需要进行捕捉
        try{
            $result = Reflection::run($result,$request,$response);
            echo PHP_EOL;
            echo '控制器方法返回的数据为:'.PHP_EOL;
            var_dump($result);
            echo PHP_EOL;
        }catch (ParamValidException $e){
            Log::getInstance()->error("[".date('Y-m-d H:i:s',time()).'HTTP ACCESS ERROR'."]".$e->getMessage().PHP_EOL);
            //解析不正常 则走这里
            return $response->end($e->getMessage());
        }
    }

    /**
     * 投递异步任务时候　task进程执行的回到函数 --＠tip 尽量不要修改逻辑
     * @param $server
     * @param $task_id
     * @param $worker_id
     * @param $data
     */
    public function task($server,$task_id,$worker_id,$data){
        //data是一个任务类对象
        //执行的记过一定要返回　通知worker进程,任务已经处理完毕 这里是一个task对象
        return $data->__hookRun($task_id,$worker_id);
    }

    //该回调函数会在task回调返回之后触发  $data为task回调的返回值
    public function finish($server,$task_id,$data){
        $data->__hookFinish($data,$task_id);
    }

    //websocket 连接回调函数  可以在request中获取当前的唯一标示fd
    public function open($server,$request){
        //websocket客户端与server端建立连接
        echo "[".date('Y-m-d H:i:s',time())." client connected,当前的客户端fd为---{$request->fd}]".PHP_EOL;
    }

    //接受客户端发送的消息 frame->fd 唯一标示 frame->data 客户端传递的数据[json数据]
    /**
     * 这里websocket长连接　主要应用于消息的实时推送 [--v0.1版本目前就做这个　　后面可以扩展为IM聊天]
     * @param $server
     * @param $frame
     * @tip 这里需要心跳包检测,用户映射
     * @author chenlin
     * @date 2019/4/4
     */
    public function message($server,$frame){
        /** @tip 所有的逻辑操作　都是在message类中进行实现　该方法无需进行其他操作 @author chenlin @date 2019/4/9 */
        $message = new Message($server,$frame);
    }

    //客户端断开连接 可以做一些数据处理操作
    public function close($server,$fd){
        //则断开映射关系
        try{
            $redis  = RedisPool::getInstance()->getObj();
        }catch (RedisException $e){
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            echo $e->getMessage();
            return ;
        }
        //选择到映射数据库
        $redis->select(BIND_DATABASE);
        //@tip 目前只有用最笨的方法来实现
        $all_user = $redis->keys('*');
        foreach($all_user as $row){
            if($redis->get($row) == $fd){
                //var_dump($redis->get($row));
                $redis->del($row);
                break;
            }
        }
        //回收redis资源
        RedisPool::getInstance()->recycleObj($redis);
        echo "当前客户端{$fd}:断开连接!".PHP_EOL;
    }
}

