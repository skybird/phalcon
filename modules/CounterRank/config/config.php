<?php
// +----------------------------------------------------------------------
// | [phalcon]
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 14-7-9 19:04
// +----------------------------------------------------------------------
// + config.php
// +----------------------------------------------------------------------

return array(
    'CounterRank' => array(
        'redis_host' => '127.0.0.1',
        'redis_port' => 6379,
        // 命名空间，建议使用 app name
        'redis_namespace' => 'wscn',
        // 分组 tokens ，键是分组名，分组是一组元素的集合，只能对同分组内的数据进行排序。type 字段是统计方式，支持 'increment' 增量数据 和 'all' 全量数据。未列出的分组不支持通过 http 客户端访问。
        'group_tokens' => array(
            'posts' => array(
                'token' => '8186b38865c422e581881b1e1d2d9740',
                'type' => 'increment'
            ),
            'livenews' => array(
                'token' => 'e432abe386f5062488e9335dfe534e7d',
                'type' => 'increment'
            ),
            'comments' => array(
                'token' => '4e1c2a79dcdd4e6e73bbdeb3f48d3d76',
                'type' => 'increment'
            )
        )
    )
);