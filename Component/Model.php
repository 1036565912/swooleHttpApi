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
     * @return mixed
     * @author chenlin
     * @date 2019/4/１
     */
    public function model(){
        // TODO: Implement model() method.
        $this->model = MysqlPool::getInstance()->getObj();
        return $this->model;
    }

    /**
     * 继承于model基类的子类 无法定义自己的构造函数 只有用initialize方法来进行一些参数的初始化
     */
    final public function initialize(){
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