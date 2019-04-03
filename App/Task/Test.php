<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-2
 * Time: 上午11:23
 */
namespace App\Task;
use AbstractInterface\AbstractTask;

/** 自定义任务投递类　来实现一个简单的类
 * @author chenlin
 * @date 2019/4/2
 */
class Test extends  AbstractTask{

    /**
     * 投递任务执行的方法
     * @author chenlin
     * @date 2019/4/2
     * @param $data
     * @param $task_id
     * @param $worker_id
     */
    public function run($data,$task_id,$worker_id){
        // TODO: Implement run() method.

        echo '我开始处理投递的任务了!'.PHP_EOL;
        var_dump('处理的数据为:'.$data);
        $this->result = true;
        return $this;

    }


    /**
     * 任务完成执行的回调方法
     * @author chenlin
     * @date 2019/4/2
     * @param $result
     * @param $task_id
     */
    public function finish($result,$task_id){
        // TODO: Implement finish() method.
        var_dump('任务已经完成！');
        var_dump($result);
        var_dump($this->result);
    }
}