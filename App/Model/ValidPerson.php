<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-7-23
 * Time: 上午11:50
 */

namespace App\Model;
use Component\Model;

class ValidPerson extends Model
{


    /** 定义常量信息 */
    /** 角色常量 */
    const LEADER_ROLE = 1; //视察领导
    const DOCTOR_ROLE = 2; //医生
    const PATIENT_ROLE = 3; //病人
    const SECURITY_ROLE = 4; //保安


    /**
     * 根据用户code获取用户的信息
     * @param array $code_arr
     * @param mixed $field 如果是多字段　则需要自己添加',' 如果是一个字段　则直接输入特定字段字符串即可
     * @author chenlin
     * @date 2019/7/23
     */
    public function getPerson(array $code_arr,  $field)
    {
        $sql = '';
        //构造返回的数据字段
        if (is_array($field)) {
            $field = implode(',',$field);
        }
        $sql .= 'SELECT '.$field.' from '.$this->model->getTable();
        //构造 in 后面的字符串　引号界定符的问题
        $sql .= ' where code in (';
        foreach ($code_arr as $code) {
            $sql .= "'{$code}'";
            $sql .= ',';
        }
        //去掉最后一个逗号
        $sql = mb_substr($sql,0,-1,'UTF-8');
        $sql .= ')';
        return $this->query($sql);
    }
}