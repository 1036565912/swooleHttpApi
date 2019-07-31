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
        'DB_PORT' => 3305,
        'DB_DATABASE' => 'ZH_hospital',
        'DB_USERNAME' => 'root3',
        'DB_PASSWORD' => 'yybigdata',
        'DB_PREFIX'   => '',
    ],
    'log' => [
        'error_path' => LOG_PATH.'/error.log',
        'log_path'   => LOG_PATH.'/log.log',
        'db_record_path' => LOG_PATH.'/db_log.log',
    ],

    'pool' => [
        'redis_minCount' => 10, //redis热加载的连接数量用于热启动
        'mysql_minCount' => 20,//mysql热加载的连接数量用于热启动
        'maxCount' => 50, //连接池最大的链接数量
    ]
];
