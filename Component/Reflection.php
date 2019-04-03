<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-3-31
 * Time: 下午10:30
 */

namespace Component;
use UserException\ParamValidException;
use Swoole\Http\Request;
use Swoole\Http\Response;


/** 通过反射类来实现 函数参数的依赖注入
 * @author chenlin
 * @date 2019/3/31
 */
class Reflection{

    protected static $namespace = "\\App\\Controller\\";
    /**
     * 根据传递的模块、控制器、方法信息来进行方法映射
     * @param array $controller_info
     * @return string
     * @author chenlin
     * @date 2019/3/31
     */
    public static function run(array $controller_info,Request $request,Response $response){
        if(count($controller_info) != 3){
            throw new ParamValidException('当前请求参数异常!');
        }

        //首先根据反射类进行当前访问的控制器进行实例化
        $current_controller = self::$namespace.$controller_info[0]."\\".$controller_info[1];
        $reflection = new \ReflectionClass($current_controller);
        $constructor = $reflection->getConstructor();

        //本来是应该进行映射来获取参数  但是这里request 、response对象无法获取当前请求的消息头信息
        //无法获取所有的数据 因此只能使用request回调提供的两个请求对象
        //$params = self::getParamValue($constructor);

        //这里直接根据request回调中的request、response对象来进行依赖注入
        $params = [$request,$response];
        //得到当前映射出来的对象
        $instance = $reflection->newInstanceArgs($params);

        //执行需要访问的方法 --方法依赖注入参数
        $method = new \ReflectionMethod($current_controller,$controller_info[2]);
        //获取需要映射的参数
        $params = self::getParamValue($method);
        //执行当前方法 并且获取结果
        $result =  $method->invokeArgs($instance,$params);
        return $result;
    }

    /**
     * 根据传递的方法去追踪当前方法需要引入参数类型
     * @param $method
     * @return array
     * @author chenlin
     * @date 2019/3/31
     */
    public static function getParamValue(\ReflectionMethod $method){
        $arvgs = [];
        if($method->getNumberOfParameters() > 0){
            foreach($method->getParameters() as $param){
                $param_type = $param->getClass(); //获取当前注入对象的类型提示
                $param_value = $param->getName(); //获取参数名称
                if($param_type){
                    //表示是对象类型的参数
                    $arvgs[] = new $param_type->name; //这里可能需要用到自动加载
                }
            }
        }
        return $arvgs;
    }
}