<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-1
 * Time: 下午5:22
 */
namespace App\Model;
use Component\Model;
use Helper\Config;
/** 自定模型操作　
 *　@tip v0.1版本　只能从设置的类名来自动识别出表明　大驼峰式　=>　自动转化成下划线的表明
 * @tip  for example  City  系统自动获取前缀.city   or  CityTest  前缀.city_test
 */
class City extends Model{

}