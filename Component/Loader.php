<?php

/** 自动加载类 */

namespace Common;
/**
 * 自动加载注册类
 * @author chenlin 
 * @date 2019/3/29
 */
class Loader{
    public static function autoload(){
        spl_autoload_register([__CLASS__,'register']);
    }

    public static function register($className){
        require ROOT_PATH.'/'.str_replace('\\','/',$className).'.php';
    }
}