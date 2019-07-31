<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-3-29
 * Time: 下午3:26
 */

namespace Pool;
use Component\SingleTon;
use Swoole\Coroutine\Channel;
use Pool\Mysql;
use UserException\MysqlException;
use UserException\ReconnectException;
use AbstractInterface\AbstractPool;
use Helper\Log;
use UserException\MaxConnectionException;
use Swoole\Coroutine;
use Helper\Config;

/**
 * Class MysqlPool
 * @package Pool
 * @tip mysql连接池
 * @author chenlin
 * @date 2019/3/29
 */
class MysqlPool implements AbstractPool {
    use SingleTon;
    private $mysqlPool;
    private $maxCount;
    private $currentCount = 0;
    private $host;
    private $port;
    private $userName;
    private $password;
    private $database;
    private $coroutineArray = []; //用来存放协程内部占用的mysql连接　　方便后面全局回收

    /**
     * MysqlPool constructor.
     * @tip 用来进行数据库连接池的初始化操作
     * @author chenlin
     * @date 2019/3/29
     */
    protected function __construct(int $minCount){
        $database_info = Config::getInstance()->get('mysql');
        $this->host = $database_info['DB_HOST'];
        $this->port = $database_info['DB_PORT'];
        $this->database =  $database_info['DB_DATABASE'];
        $this->userName = $database_info['DB_USERNAME'];
        $this->password = $database_info['DB_PASSWORD'];
        $this->maxCount = Config::getInstance()->get('pool.maxCount');
        //创建一个mysql连接池
        $this->mysqlPool = new Channel($this->maxCount);
        for($i=0;$i<$minCount;$i++){
            $mysql = new Mysql();
            $connect_info = [
                'host' => $this->host,
                'port' => $this->port,
                'user' => $this->userName,
                'password' => $this->password,
                'database' => $this->database,
                'charset'  => 'utf8',
                'strict_type' => false, //开启严格模式　则返回的数据也将转化为强类型模式
                'fetch_mode' => true, //可以像pdo一样，fetch\fetchAll逐行获取
            ];
            if(!$mysql->connect($connect_info)){
                throw new MysqlException('coroutine mysql initialize error!');
            }

            $this->mysqlPool->push($mysql);
            $this->currentCount++;
        }
    }

    /**
     * 获取mysql对象
     * @return Swoole\Coroutine\Mysql
     * @author chenlin
     * @date 2019/3/29
     */
    public function getObj(){
        // TODO: Implement getObj() method.
        if(!$this->mysqlPool->isEmpty()) {
//            echo PHP_EOL.'当前的mysql连接池中的数目为:'.PHP_EOL;
//            var_dump($this->check_length());
//            echo '-------------------------------'.PHP_EOL;
            //然后还进行断线重连校验
            $obj =  $this->mysqlPool->pop();
//            echo '获取之后,资源数目还有'.$this->check_length().PHP_EOL;
//            var_dump($obj);
            return $this->check_over($obj);
        }
        //代表已经没有空闲的mysql连接
        if($this->maxCount == $this->currentCount){
            //echo '当前系统已经无法接受新的请求'.PHP_EOL;
            //代表已经饱和 直接返回false
            throw new MaxConnectionException('当前连接池资源已经达到上限，且没有空闲资源可以获取!');
        }else if($this->maxCount > $this->currentCount){
            //echo '当前系统正在创建新的资源连接,请稍等!'.PHP_EOL;
            //则创建新的连接
            $mysql = new Mysql();
            $connect_info = [
                'host' => $this->host,
                'port' => $this->port,
                'user' => $this->userName,
                'password' => $this->password,
                'database' => $this->database,
                'charset'  => 'utf8',
                'strict_type' => false, //开启严格模式　则返回的数据也将转化为强类型模式
                'fetch_mode' => true, //可以像pdo一样，fetch\fetchAll逐行获取
            ];
            if(!$mysql->connect($connect_info)){
                throw new MysqlException('创建新的协程mysql链接失败!');
            }

            $this->currentCount++;

            return $mysql;
        }

    }

    /**
     * @param $obj
     * @return bool
     */
    public function recycleObj($obj){
        // TODO: Implement recycleObj() method.
        //echo PHP_EOL.'开始回收资源'.PHP_EOL;
        $obj->reset();
        if (!$this->mysqlPool->push($obj)) {
            $this->currentCount--;
            return false;
        }
        return true;
    }


    /**
     * @tip 这里目前这么写　　可能存在一定的效率损耗
     * 由于mysql　server存在杀死空闲连接的特性　所以每次获取连接的时候　都要进行断线重连机制
     * @author chenlin
     * @date 2019/3/29
     * @param $obj 数据库连接对象
     */
    protected function check_over(Mysql $obj){
        $sql = "show databases";
        $result = $obj->query($sql);
        if(!$result){
            //校验是否是断线状态
            if($obj->errno == 2006 || $obj->errno == 2013){
                echo '----------------------------'.PHP_EOL;
                echo '进行了断线重连!'.PHP_EOL;
                echo '----------------------------'.PHP_EOL;
                //代表是已经断线　
                Log::getInstance()->record('[DB Reconnect '.date('Y-m-d H:i:s',time()).']错误信息为:'.$obj->error.',错误code为:'.$obj->errno.PHP_EOL);
                $obj = $this->reconnect($obj);
            }
        }
        return $obj;
    }

    /**
     * @tip 当检测mysql连接已经断开的时候，则进行断线重连机制
     * @param Mysql $obj
     * @return mysql
     * @author chenlin
     * @date 2019/3/29
     */
    protected function reconnect(Mysql $obj){
        $result = $obj->connect([
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->userName,
            'password' => $this->password,
            'database' => $this->database,
            'charset'  => 'utf8',
            'strict_type' => false, //开启严格模式　则返回的数据也将转化为强类型模式
            'fetch_mode' => true, //可以像pdo一样，fetch\fetchAll逐行获取
        ]);
        if(!$result){
            $this->currentCount--;
            throw new ReconnectException('断线重连机制重连失败!');
        }

        /** 这里需要增加一个修改连接对象属性的策略　因为查询正确　是不会修改errno error */
        $obj->error = "";
        $obj->errno = 0;

        return $obj;
    }

    /**
     * 查看当前的进程池中的空闲的连接数目
     * @return int
     * @author chenlin
     * @date 2019/4/1
     */
    public function check_length(){
        return $this->mysqlPool->length();
    }

    /**
     * 添加协程获取的连接资源到全局管理数组中　用于后面统一回收
     * @param \Pool\Mysql $mysql
     * @return bool
     * @author chenlin
     * @date 2019/7/25
     */
    public function addConnect(Mysql $mysql)
    {
        if(Coroutine::getCid() === -1){
            echo PHP_EOL.'当前环境不是协程环境,请检查!'.PHP_EOL;
            exit();
        }
        $this->coroutineArray[Coroutine::getuid()][] = $mysql;
        return true;
    }

    /**
     * 全局回收当前协程获取到的mysql连接资源到连接池　
     * @param  void
     * @return bool
     * @author chenlin
     * @date 2019/7/25
     */
    public function globalRecycle()
    {
        if(Coroutine::getCid() === -1){
            echo PHP_EOL.'当前环境不是协程环境,请检查!'.PHP_EOL;
            exit();
        }
        $cid = Coroutine::getCid();
//        echo PHP_EOL.'当前协程环境'.$cid.'正在进行资源回收'.PHP_EOL;
//        echo '当前的连接池中的资源数量'.$this->check_length().PHP_EOL;
        //只有存在当前协程环境中存在的mysql资源需要回收
        if (isset($this->coroutineArray[$cid])) {
            foreach ($this->coroutineArray[$cid] as $mysql) {
                $this->recycleObj($mysql);
            }
        }
//        echo '回收之后,连接池中的资源数量为'.$this->check_length().PHP_EOL;
        //删除当前协程保存的记录　防止内存泄露
        unset($this->coroutineArray[$cid]);
        return true;
    }
}