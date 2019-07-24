<?php

/** 配置文件信息 可以跟自己的需求去动态添加配置信息 */

//自定义配置信息文件
return [
    'redis' => [
        'host' => '127.0.0.1',   //0.0.0.0
        'port' =>  6379,
        'timeOut' => 2,
    ],
    'SMS_CODE' => [
    ],
    'mysql' => [
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => 3306,
        'DB_DATABASE' => 'test',
        'DB_USERNAME' => 'root',
        'DB_PASSWORD' => 'cl123456',
        'DB_PREFIX'   => '',
    ],
    'log' => [
        'error_path' => LOG_PATH.'/error.log',
        'log_path'   => LOG_PATH.'/log.log',
        'db_record_path' => LOG_PATH.'/db_log.log',
    ],

    'pool' => [
        'minCount' => 1,//用于热启动
        'maxCount' => 5, //连接池最大的链接数量
    ]
];
