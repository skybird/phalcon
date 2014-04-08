<?php

return array(
    'debug' => 0,
    'baseUri' => '/',

    'app' => array(
        'title' => 'EvaEngine',
        'subtitle' => '',
    ),

    'logger' => array(
        'adapter' => 'File',
        'path' => __DIR__ . '/../logs/',
//        'defaultName' => 'system',
    ), 

    'translate' => array(
        'enable' => true,
        'path' => __DIR__ . '/../languages/',
        'adapter' => 'csv',
        'forceLang' => 'zh_CN', 
    ),

    'routes' => array(

    ),

    'filesystem' => array(
        'adapter' => 'local',
        'uploadPath' => __DIR__ . '/../public/uploads/',
        'uploadUrlBase' => '/uploads',
    ),

    'dbAdapter' => array(
        'master' => array(
            'driver' => 'Pdo_Mysql',
            'host' => '192.168.1.228',
            'database' => 'eva',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8'
        ),
        'slave' => array(
            'slave1' => array(
                'driver' => 'Pdo_Mysql',
                'host' => '192.168.1.233',
                'database' => 'eva',
                'username' => 'root',
                'password' => '',
                'charset'  => 'utf8'
            ),
        )
    ),

    'modelsMetadata' => array(
        'enable' => true,
        'adapter' => 'File',
        'options' => array(
            'metaDataDir' => __DIR__ . '/../cache/schema/'
        
        ),
    ),

    'queue' => array(
        'servers' => array(
            'server1' => array(
                'host' => '127.0.0.1',
                'port' => 4730,
            ),
        ),
    ),

    'mailer' => array(
        'async' => false,
        'transport' => 'smtp', //or default
        'host' => 'smtp.gmail.com',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'username',
        'password' => 'password',
        'defaultFrom' => array('noreply@wallstreetcn.com' => 'WallsteetCN'),
        'systemPath' => 'http://evaengine.com/',
        'staticPath' => 'http://evaengine.com/',
    ),

);
