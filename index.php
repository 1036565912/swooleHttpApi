<?php

/** 项目根目录 */

//定义路径常量信息

//根目录常量
define('ROOT_PATH',__DIR__);
//日志目录常量
define('LOG_PATH',ROOT_PATH.DIRECTORY_SEPARATOR.'Log');


//引入自动加载类
require "Component/Loader.php";
//注册自动加载
Common\Loader::autoload();

//由于公共方法没有无法自动加载　这里只能手动载入
require "Common/functions.php";

use Server\Websocket;
/**　参数格式: 地址　端口 其实可以通过命令行来动态添加  $argv*/

$cli_host_array = $argv;
array_shift($cli_host_array); //移除命令行传递的一个无效参数
if(!is_array($cli_host_array) || count($cli_host_array) != 2){
    die('参数不合法!');
}
//现在的写法
$server = new Websocket($cli_host_array[0],$cli_host_array[1]);


