<?php

/** 单例 trait */
namespace Component;
/**
 * 单例模式trait　为后面所有单例模式的类提供了水平代码复用的可能
 * @author chenlin
 * @date 2019/3/29
 */
 trait SingleTon{
     private static $instance;
     public static function  getInstance(...$args){
         if(empty(self::$instance)){
            //echo '初始化的当前类:'.static::class.PHP_EOL;
            self::$instance = new static(...$args);
         }
         return self::$instance;
     }
 }