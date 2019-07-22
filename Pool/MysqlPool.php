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
use AbstractInterface\AbstractPool;

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


    /**
     * MysqlPool constructor.
     * @tip 用来进行数据库连接池的初始化操作
     * @author chenlin
     * @date 2019/3/29
     */
    protected function __construct(int $minCount){
        $database_info = config('mysql');
        $this->host = $database_info['DB_HOST'];
        $this->port = $database_info['DB_PORT'];
        $this->database =  $database_info['DB_DATABASE'];
        $this->userName = $database_info['DB_USERNAME'];
        $this->password = $database_info['DB_PASSWORD'];
        $this->maxCount = config('pool.maxCount');
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
        if($this->mysqlPool->length()) {
            //然后还进行断线重连校验
            $obj =  $this->mysqlPool->pop();
            return $this->check_over($obj);
        }
        //代表已经没有空闲的mysql连接
        if($this->maxCount == $this->currentCount){
            //代表已经饱和 直接返回false
            return false;
        }else if($this->maxCount > $this->currentCount){
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
        $obj->reset();
        return $this->mysqlPool->push($obj);
    }


    /**
     * @tip 这里目前这么写　　可能存在一定的效率损耗
     * 由于mysql　server存在杀死空闲连接的特性　所以每次获取连接的时候　都要进行断线重连机制
     * @author chenlin
     * @date 2019/3/29
     * @param $obj 数据库连接对象
     */
    protected function check_over( Mysql $obj){
        $sql = "show databases";
        $result = $obj->query($sql);
        if(!$result){
            //校验是否是断线状态
            if($obj->errno == 2006 || $obj->errno == 2013){
                //代表是已经断线　
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
            throw new MysqlException('断线重连机制重连失败!');
        }

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
}