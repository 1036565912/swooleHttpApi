<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-3-29
 * Time: 下午5:28
 */
namespace  App\Controller;
use Swoole\Http\Request;
use Swoole\Http\Response;
/**
 * 基类控制器　
 * @tip 暂时不知道基类该干嘛
 * Class Controller
 * @package App\Controller
 */
abstract  class Controller{

    //http请求的时候 请求 响应变量

    protected  $request;
    protected  $response;

    /**
     * 最终类构造函数
     * @param  $request Swoole\Http\Request
     * @param  $response Swoole\Http\Response
     * @tip 用来进行输出 、 输出的对象的注入
     * Controller constructor.
     */
    final public function __construct(Request $request, Response $response){
        $this->request = $request;
        $this->response = $response;
    }

    /** 暂时定义一个基类 */
    abstract public function index();
}