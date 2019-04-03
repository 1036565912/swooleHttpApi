<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-2
 * Time: 下午1:51
 */

namespace Helper;
use AbstractInterface\AbstractTask;
use Swoole\Server;
use UserException\ParamTypeErrorException;
/** 任务管理类 用来进行任务的投递工作 @author:chenlin @date:2019/4/2 */
class TaskManager{
    /**
     * 投递一个异步[非阻塞]任务
     * @author chenlin
     * @date 2019/4/2
     */
    public static function async(AbstractTask $task){
        //现在的写法
        return ServerManager::getInstance()->getSwooleServer()->task($task);
    }

    /**
     * 投递一个异步阻塞任务
     * @author chenlin
     * @date 2019/4/3
     */
    public static function sync(AbstractTask $task,float $timeout=0.5){
        return ServerManager::getInstance()->getSwooleServer()->taskwait($task,$timeout);
    }

    /**
     * 投递多个同步的任务[返回的结果按照你传递的参数顺序返回]
     * @author chenlin
     * @date 2019/4/3
     */
    public static function syncMulti(array $tasks,float $timeout=0.5){
        //为了防止投递的不是任务类导致出现异常　这里进行异常捕捉
        foreach($tasks as $task){
            if(!($task instanceof AbstractTask)){
                throw new ParamTypeErrorException('当前投递的任务不属于AbstractTask类型');
            }
        }
        return ServerManager::getInstance()->getSwooleServer()->taskWaitMulti($tasks,$timeout);
    }
}