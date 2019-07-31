<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-7-23
 * Time: 上午11:51
 */

namespace App\Model;
use Component\Model;

class MessageType extends Model
{

    //定义部分状态常量
    /** 对象类型常量 */
    const PERSON_STATUS = 1;  //个人id
    const GROUP_STATUS = 2;    //用户组code

}