<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 18-10-10
 * Time: 下午4:40
 */
use Swoole\Http\Request;
use Swoole\Http\Response;

 /** 这个是公共函数库　这里不用命名空间引入 */
/**
 * @param $string 需要返回的key
 * @return mixed
 * @author chenlin
 */
function config($string = '') {
    $data = require ROOT_PATH.'/config.php';
    if(empty($string)){
        return $data;
    }
    $keys = explode('.',$string);
    $result = $data;
    foreach ($keys as $key){
        $result = $result[$key];
    }
    return $result;
}

/**
 * 检测客户端是否是websocket连接
 * @return bool
 * @auhtor chenlin
 * @date 2019/3/29
 */
function checkClient($server,$fd) :bool {
	$info = $server->connection_info($fd);
	if($info && isset($info['websocket_status']) && $info['websocket_status'] > 0){
		return true;
	}
	return false;
}

/**
 * 进行驼峰字符串的大写转化成下划线的格式重塑
 * @param string $string
 * @return string
 * @author chenlin
 * @date 2019/4/1
 */
function format_reload(string $string){
    $tmp_str = '';
    for($i = 0; $i<mb_strlen($string); $i++){
        $ascii_code = ord($string[$i]);
        //A-Z ascii码值为65~90
        if($ascii_code >= 65 && $ascii_code <= 90){
            if($i == 0){
                $tmp_str .= chr($ascii_code+32);
            }else{
                $tmp = '_'.chr($ascii_code+32);
                $tmp_str .= $tmp;
            }
        }else{
            $tmp_str.= chr($ascii_code);
        }
    }
    return $tmp_str;
}

/**
 * 由于google浏览器在进行一次请求的时候　会发送两个请求　其中有一个是请求是请求iconv
 * @author chenlin
 * @date 2019/4/4
 * @return bool
 */
function refuseIconv(Request $request,Response $response){
    $uri = $request->server['request_uri'];
    if($uri == '/favicon.ico'){
       return false;
    }
    return true;
}
