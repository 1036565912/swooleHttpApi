<?php
/**
 * Created by PhpStorm.
 * User: chen
 * Date: 19-7-22
 * Time: 下午5:35
 */

namespace Message;
use AbstractInterface\AbstractMessage;

/** 消息推送message类 @author:chenlin @date:2019/7/22 */
class PushMessage extends AbstractMessage
{
    /**
     *消息处理方法　
     * @auhtor chenlin
     * @date 2019/7/22
     */
    public function deal()
    {
        // TODO: Implement deal() method.

    }

    /**
     * @param bool $flag
     * @param string $data
     */
    public function push(bool $flag = true, $data = '')
    {
        // TODO: Implement push() method.
    }
}