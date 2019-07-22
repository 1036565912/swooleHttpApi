<?php
use Server\Websocket;

/** 项目根目录 */

//定义路径常量信息

//根目录常量
define('ROOT_PATH',__DIR__);
//日志目录常量
define('LOG_PATH',ROOT_PATH.DIRECTORY_SEPARATOR.'Log');
//定义用户映射数据库
define('BIND_DATABASE',0);
//定义用户历史消息数据库
define('HISTORY_DATABASE',10);




//引入自动加载类
require "Component/Loader.php";
//引入composer自动加载
require 'vendor/autoload.php';

//注册自动加载 @tip 由于框架本身也包含了自动加载　但是如果使用了第三方库　那么就需要引入自己的自动加载类　和　自己的自动加载类
Common\Loader::autoload();

//由于公共方法无法自动加载　这里只能手动载入 @tip　采用了psr-4自动加载  不需要自己引入了
//require "Common/functions.php";


/**　参数格式: 地址　端口 其实可以通过命令行来动态添加  $argv*/

$cli_host_array = $argv;
array_shift($cli_host_array); //移除命令行传递的一个无效参数
if(!is_array($cli_host_array) || count($cli_host_array) != 2){
    die('参数不合法!');
}
//现在的写法
$server = new Websocket($cli_host_array[0],$cli_host_array[1]);


