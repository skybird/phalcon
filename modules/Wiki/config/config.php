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
    'counter' => array(
        'redis_host' => 'localhost',
        'redis_port' => 6379,
        'redis_namespace' => 'wscn',
        'group_tokens' => array(
            'posts' => '8186b38865c422e581881b1e1d2d9740',
            'comments' => '4e1c2a79dcdd4e6e73bbdeb3f48d3d76'
        )
    )
);