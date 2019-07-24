<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-3-29
 * Time: 下午5:26
 */
namespace App\Controller\Index;
use App\Controller\Controller;
use App\Model\User;
use Helper\TaskManager;
use App\Task\Test;
use Pool\RedisPool;
use UserException\ParamTypeErrorException;
use UserException\MysqlException;
use Helper\Log;
use UserException\RedisException;

class Index extends Controller{

    public function index(){
        //由于获取mysql资源的时候　可能抛出异常
        try{
            $city_model = new User();
        }catch(MysqlException $e){
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return false;
        }
        $result = $city_model->test();
        $city_model->recyle(); //回收mysql对象

        try {
            $redis = RedisPool::getInstance()->getObj();
        } catch (RedisException $e){
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return false;
        }

        $redis->select(10);
        $redis->lPush('test','this is a test text');
        RedisPool::getInstance()->recycleObj($redis);
        return $this->response->end(json_encode($result));
    }

    public function task(){
        //投递一个任务
        $task_one = new Test('测试');
        //投递一个异步非阻塞任务
        //return TaskManager::async($task);
        //投递一个异步阻塞任务
        //$result = TaskManager::sync($task);  //result 是一个test类对象　　$result->result 是处理的结果
        //return $result;
        $task_two = new Test('哈哈');
        try{
            $result = TaskManager::syncMulti([$task_one,$task_two]);
            var_dump($result);   //结果是一个test对象数组　　$result[i]->result是处理的结果
        }catch(ParamTypeErrorException $e){
            var_dump($e->getMessage());
        }
        return true;
    }


    public function test(){
        return $this->response->end(json_encode($this->request->get));
    }

}