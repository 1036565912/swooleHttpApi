<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-4-4
 * Time: 下午5:02
 */

namespace Message;
use AbstractInterface\AbstractMessage;
use Pool\MysqlPool;
use Pool\RedisPool;
use Helper\Log;
use UserException\MysqlException;
use UserException\RedisException;
use App\Model\MessagePush;
use UserException\MaxConnectionException;
use UserException\ReconnectException;

/**
 * 信息映射消息处理
 * Class BindMessage
 * @package Message
 * @author chenlin
 * @date 2019/4/4
 */
class BindMessage extends AbstractMessage{
    public function deal(){
        echo 'client:'.$this->frame->fd.'建立关系映射,对应的用户id为:'.$this->data.PHP_EOL;
        //获取redis实例 由于初始化的时候　可能抛出异常
        try {
            $redis = RedisPool::getInstance()->getObj();
        }catch (RedisException $e){
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return $this->push(false,'系统异常,无法进行关系绑定');
        }catch (MaxConnectionException $e) {
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return $this->push(false,'系统繁忙,无法处理你的请求');
        }

        $redis->select(BIND_DATABASE);
        //进行用户信息与fd的绑定
        $result = $redis->set($this->data,$this->frame->fd);
        //并且需要存入反转数据库
        $redis->select(REFLECTION_DATABASE);
        $redis->set($this->frame->fd,$this->data);

        //进行历史消息的推送
        $this->historyPush($redis,$this->data);

        //资源回收
         RedisPool::getInstance()->recycleObj($redis);

        if($result){
            return $this->push();
        }else{
            return $this->push(false);
        }
    }

    //@tip 这里应该加一个成功与否的标志
    public function push(bool $flag = true,$data = ''){
        // TODO: Implement push() method.
        //message操作　最后都会走到push 所以就在这里进行全局回收mysql资源
        MysqlPool::getInstance()->globalRecycle();
        //@tip 由于存在问题
        $result = [
            'method' => 'bind',
            'result' => $data,
            'code'   => $flag ? self::SUCCESS_CODE : self::ERROR_CODE,
        ];
        return $this->server->push($this->frame->fd,json_encode($result));
    }

    /**
     * 历史消息推送
     * @param \Redis $redis　redis连接资源
     * @param string $user_id 建立映射关系的用户id
     * @return bool
     * @author chenlin
     * @date 2019/7/22
     */
    public function historyPush(\Redis $redis,string $user_id)
    {
        echo '进行了历史消息的推送'.PHP_EOL;
        //实例化一个消息推送历史数据的模型
        try {
            $messagePush = new MessagePush();
        } catch (MysqlException $e) {
            Log::getInstance()->error('[record messagePush Error '.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return false;
        } catch (MaxConnectionException $e){
            Log::getInstance()->error('[record messagePush Error '.date('Y-m-d H:i:s',time()).']----'.$e->getMessage().PHP_EOL);
            return false;
        } catch (ReconnectException $e) {
            Log::getInstance()->error('['.date('Y-m-d H:i:s',time()).' Model 初始化失败]'.$e->getMessage().PHP_EOL);
            return false;
        }

        //选择历史数据库
        $redis->select(HISTORY_DATABASE);
        $length = $redis->lLen($user_id);
        for ($i=1; $i<=$length; $i++) {
            $info = $redis->rPop($user_id);
            $info = json_decode($info,true);
            $messagePush->insertOne($info['message_type'],$info['text'],$user_id,$info['time'],$info['place_id'],$info['camera_id']);
            if (checkClient($this->server,$this->frame->fd)) {
                $this->server->push($this->frame->fd,pushMessage($info['text']));
            } else {
                Log::getInstance()->error('[Type Error: '.date('Y-m-d H:i:s',time()).']Fd =>'.$this->frame->fd.'对应的user_id =>'.$user_id.'不是websocket client'.PHP_EOL);
            }

        }
        return true;
    }
}