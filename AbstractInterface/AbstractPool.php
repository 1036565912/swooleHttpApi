<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-3-29
 * Time: 下午2:15
 */

namespace AbstractInterface;

/**
 * 连接池定义规范
 * @author chenlin
 * @date 2019/3/29
 */
interface AbstractPool{
    public function getObj();
    public function recycleObj($obj);
}