<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-1
 * Time: 下午9:20
 */

namespace Helper;
use Component\SingleTon;
/** 加载配置文件类 用来解决每次都获取配置文件都需要引入文件导致带来的性能的损耗
 * 这里后面扩展一个动态加载配置信息 实现数据的共享 【这里只有在server start之前初始化】
 * @author chenlin
 * @date 2019/4/1
 */
class Config{
    use SingleTon;
    protected  $info = '';//用来存放静态配置文件信息
    protected  function __construct(){
        $this->info = require ROOT_PATH.'/config.php';
    }

    /**
     * @param string $key
     */
    public function get(string $key = ''){
        if(empty($key)){
            return $this->info;
        }
        $keys = explode('.',$key);
        $result = $this->info;
        foreach ($keys as $key){
            $result = $result[$key];
        }
        return $result;
    }
}