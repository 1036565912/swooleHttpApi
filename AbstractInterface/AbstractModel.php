<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-1
 * Time: 下午4:48
 */

namespace AbstractInterface;

/** 数据库模型统一接口 @author:chenlin @date:2019/4/1 */
interface  AbstractModel{
    public function model();
    public function initialize();
}