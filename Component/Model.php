<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-1
 * Time: 下午4:43
 */

namespace Component;
use Pool\MysqlPool;
use Helper\Config;
use AbstractInterface\AbstractModel;
use UserException\MaxConnectionException;
use UserException\MysqlException;
use UserException\ReconnectException;
use Swoole\Coroutine;

/** 基类model类 定义基本的系列规范 @author:chenlin @date:2019/4/1 */
abstract class Model implements AbstractModel {
    //当前的mysql连接对象
    protected  $model;

    final public function __construct(){
        $this->model();
        $this->initialize();
    }

    /**
     * 从连接池获取一个连接资源
     * @throws  MysqlException | MaxConnectionException | ReconnectException
     * @return mixed
     * @author chenlin
     * @date 2019/4/１
     */
    public function model(){
        // TODO: Implement model() method.
        $this->model = MysqlPool::getInstance()->getObj();
        //由于获取mysql资源会抛出异常  如果没有被外层捕获　　就代表　拿到了mysql资源
//        echo PHP_EOL.'获取到的mysql连接'.PHP_EOL;
//        var_dump($this->model);
        $cid = Coroutine::getCid();
        if ($cid === -1) {
            echo PHP_EOL.'当前系统所处的环境不是协程环境,请检查!'.PHP_EOL;
            exit();
        }
        MysqlPool::getInstance()->addConnect($this->model);
    }

    /**
     * 默认根据model名称去自动分割成下划线　然后去查询数据
     * @tip 默认是不允许进行重写基类的initialize方法
     * @tip 如果一定要重写　请去掉final修饰词
     */
     public function initialize(){
        //模型跟表进行关联
        $current_class = strrchr(static::class,'\\'); //static 在哪里调用就代表哪个类对象   self则是代表被定义的类对象
        $current_class = trim($current_class,'\\');
        //var_dump($current_class);
        $table_name = Config::getInstance()->get('mysql.DB_PREFIX').format_reload($current_class);
        //var_dump('当前的表名:'.$table_name);
        $this->model->setTable($table_name);
    }

    /**
     * 用来调用mysql的操作方法
     * @param $name
     * @param $arguments
     * @return mixed
     * @author chenlin
     * @date 2019/4/1
     */
    public function __call($name, $arguments){
        // TODO: Implement __call() method.
      return call_user_func_array([$this->model,$name],$arguments);

    }

    /**
     * 收回mysql连接对象
     */
    public function recyle(){
        MysqlPool::getInstance()->recycleObj($this->model);
    }
}