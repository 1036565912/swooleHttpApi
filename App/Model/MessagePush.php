<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-7-23
 * Time: 下午5:27
 */

namespace App\Model;
use Component\Model;

class MessagePush extends Model
{
    /**
     * 添加一个推送的历史记录
     * @param int $message_type 消息类型id
     * @param string $text　推送的文本信息
     * @param int $push_id 推送的用户id
     * @param string $time　消息生成的时间
     * @param int $place_id 场所id
     * @param int $camera_id 设备id
     * @return mixed
     * @author chenlin
     * @date 2019/7/23
     */
    public function insertOne(int $message_type, string $text, int $push_id, string $time, int $place_id, int $camera_id)
    {
        $sql = 'INSERT INTO '.$this->model->getTable().' (createtime,type,content,push_id,place_id,camera_id) values(';
        $sql .= "'{$time}',{$message_type},'{$text}',{$push_id},{$place_id},{$camera_id})";
        return $this->query($sql);
    }
}