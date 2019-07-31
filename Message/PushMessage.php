<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-7-22
 * Time: 下午5:35
 */

namespace Message;
use AbstractInterface\AbstractMessage;
use App\Model\Identification;
use Helper\Log;
use App\Model\VipDefine;
use App\Model\MessageType;
use App\Model\ValidPerson;
use App\Model\AdministrationGroup;
use Pool\RedisPool;
use Pool\MysqlPool;
use UserException\MaxConnectionException;
use UserException\RedisException;
use App\Model\MessagePush;
use App\Model\PlaceInfo;
use UserException\MysqlException;
use UserException\ReconnectException;

/** 消息推送message类 @author:chenlin @date:2019/7/22 */
class PushMessage extends AbstractMessage
{
    /**
     * 获取算法部发送的监测数据
     * @auhtor chenlin
     * @date 2019/7/22
     */
    public function deal()
    {
        echo '收到人脸检测的数据,准备解析-推送'.PHP_EOL;
        // TODO: Implement deal() method.
        //首先进行用户访问记录的添加
        try {
            $identification = new Identification();
            $vipDefine = new VipDefine();
            $messageType = new MessageType();
            $adminGroup = new AdministrationGroup();
            $validPerson = new ValidPerson();
            $messagePush = new MessagePush();
            $placeInfo = new PlaceInfo();
        }catch (MysqlException $e) {
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).' Model 初始化失败]'.$e->getMessage().PHP_EOL);
            return  $this->push(false,'系统发生错误,无法实时推送数据!');
        }catch (ReconnectException $e) {
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).' Model 初始化失败]'.$e->getMessage().PHP_EOL);
            return  $this->push(false,'系统发生错误,无法实时推送数据!');
        }catch (MaxConnectionException $e) {
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).' 连接池暂无可用资源]'.$e->getMessage().PHP_EOL);
            return  $this->push(false,'系统发生错误,无法实时推送数据!');
        }

        //整理需要添加记录的数据
        $fields = ['`code`','`createtime`','`place_id`','`camera_id`','`date`'];
        $values = [];
        //严格校验
        if ($this->data['result']) {
            foreach ($this->data['result']['recognition'] as $row) {
                $tmp['code'] = $row;
                $tmp['createtime'] =  str_replace('_',' ',$this->data['result']['timestamp']);
                $tmp['place_id'] = intval($this->data['result']['place_id']);
                $tmp['camera_id'] = intval($this->data['result']['camera_id']);
                $tmp['date'] = date('Y-m-d',time());
                array_push($values,$tmp);
            }
        }
        //查询正常人员检测记录
        if (!$identification->addData($fields,$values)) {
            Log::getInstance()->error('[DB QUERY ERROR:'.date('Y-m-d H:i:s',time()).'] 正常人员数据插入失败!原始数据流为:'.json_encode($this->data).PHP_EOL);
        }

        $place_name = $placeInfo->where(['id' => $this->data['result']['place_id']])->field(['id','remark','code'])->first();
        $place_name = $place_name[0]['remark'];  //场所名称
        $push_id = [];  //需要推送的人员数组
        //获取redis资源连接
        try {
            $redis = RedisPool::getInstance()->getObj();
        } catch (RedisException $e) {
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return $this->push(false,'系统发生错误,初始化redis资源失败');
        } catch (MaxConnectionException $e) {
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return $this->push(false,'系统发生错误,系统无空闲redis资源');
        }
        //判断是否有重点关注的用户
        if (isset($this->data['result']['special'])) {
            //只有这里面存在数据　才会进行消息的推送
            foreach ($this->data['result']['special'] as $key => $row) {
                //从vip_define 中获取特定的数据
                $vip_data = $vipDefine->where(['id' => $row[0]])->field(['id','message_id'])->first();  //这里是一个二维数组
                if ($vip_data === false) {
                    //获取数据失败　那么跳过当前检测的所有数据
                    Log::getInstance()->error('[DB_QUERY_ERROR'.date('Y-m-d H:i:s',time()).']查询vip_define表的时候，发生了错误!错误主键:'.$row[0].PHP_EOL);
                    return  $this->push(false,'系统发生错误,无法实时推送数据!');
                }

                //获取数据成功 获取关联的数据
                $message_data = $messageType->where(['id' => $vip_data[0]['message_id']])->field(['id','name','mould','push_object','push_id'])->first(); //这里是一个二维数组
                if ($message_data[0]['push_object'] == MessageType::PERSON_STATUS) {
                    $push_id = [
                        $message_data[0]['push_id']
                    ];
                } else {
                    //如果是成员组  需要获取当前用户组所有成员id
                    $member_data = $adminGroup->where(['id' => $message_data[0]['push_id']])->field(['id','members'])->first(); //二维数组
                    $push_id = explode(',',$member_data[0]['members']);
                }

                //补全当前需要推送的文本消息
                //获取当前有多少个需要注意的人员
                $code_arr = array_values($row);
                array_shift($code_arr);
                $person_data = $validPerson->getPerson($code_arr,['code','name','sex','role']);

                //拼接推送文本
                $push_text = $place_name.',';
                $push_text .= $message_data[0]['name'].$message_data[0]['mould'];
                //拼接人名
                foreach ($person_data as $person) {
                    $push_text .= $person['name'];
                    $push_text .= ',';
                }
                $push_text = mb_substr($push_text,0,-1,'UTF-8');
                $push_text .= '.';
                //开始推送
                foreach ($push_id as $user_id) {
                    //选择映射关系库
                    $redis->select(BIND_DATABASE);
                    //校验当前用户是否在线
                    if ($redis->exists($user_id)) {
                        //在线
                        $user_fd = $redis->get($user_id);
                        //存入历史数据库
                        $messagePush->insertOne($message_data[0]['id'],$push_text,$user_id,str_replace('_',' ',$this->data['result']['timestamp']),$this->data['result']['place_id'],$this->data['result']['camera_id']);
                        if (checkClient($this->server,$user_fd)) {
                            $this->server->push($user_fd,pushMessage($push_text));
                        } else {
                            Log::getInstance()->error('[Type Error: '.date('Y-m-d H:i:s',time()).']Fd =>'.$user_fd.'对应的user_id =>'.$user_id.'不是websocket client'.PHP_EOL);
                        }
                    } else {
                        //不在线  则需要添加到历史数据库
                        $redis->select(HISTORY_DATABASE);
                        $redis->lPush($user_id,json_encode([
                            'message_type' => $message_data[0]['id'],
                            'text' => $push_text,
                            'time' => str_replace('_',' ',$this->data['result']['timestamp']),
                            'place_id' => $this->data['result']['place_id'],
                            'camera_id' => $this->data['result']['camera_id'],
                        ]));
                    }

                }
            }
            //回收资源
            RedisPool::getInstance()->recycleObj($redis);
            return $this->push(true,'消息推送成功');
        }
    }

    /**
     * @param bool $flag
     * @param string $data
     */
    public function push(bool $flag = true, $data = '')
    {
        MysqlPool::getInstance()->globalRecycle();
        // TODO: Implement push() method.
        $arr = [
            'code' => $flag ? self::SUCCESS_CODE : self::ERROR_CODE,
            'result'  => $data,
            'method' => 'push'
        ];
        return $this->server->push($this->frame->fd,json_encode($arr));
    }
}