<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-2
 * Time: 上午10:51
 */

namespace AbstractInterface;

/** 任务模板接口类 @author chenlin */
abstract  class AbstractTask{
    protected  $data;  //用来保存需要处理的数据
    public $result;  //用来保存已经处理好的数据
    public function __construct($data){
        $this->data = $data;
    }

    /**
     * 在task回调中执行的方法  一定要return 结果到worker进程
     * @param $task_id
     * @param $worker_id
     * @return mixed
     * @author chenlin
     * @date 2019/4/1
     */
    public function __hookRun($task_id,$worker_id){
        return $this->run($this->data,$task_id,$worker_id);
    }

    /**在finish回调中执行的方法　
     * @param $result
     * @param $task_id
     * @author chenlin
     * @date  2019/4/2
     */
    public function __hookFinish($result,$task_id){
       return  $this->finish($result,$task_id);
    }
    abstract  public function run($data,$task_id,$worker_id); //任务执行方法
    abstract  public function finish($result,$task_id); //任务完成回调方法
}