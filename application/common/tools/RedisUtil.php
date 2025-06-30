<?php
/**
 * Created by PhpStorm.
 * User: rudy
 * Date: 17-10-26
 * Time: 下午6:14
 */

namespace app\common\tools;


use Redis;

class RedisUtil
{
    private static $instance = null;

    private static $options = [
        'host'       => '120.79.102.53',
        'port'       => 6377,
        'password'   => '0D9F8640-3FB1-4A56-9E26-646F71EE2E36',
        'timeout'    => 0,
        'expire'     => 0,
    ];

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Redis;
            self::$instance->connect(self::$options['host'], self::$options['port'], self::$options['timeout']);
            if ('' != self::$options['password']) {
                self::$instance->auth(self::$options['password']);
            }
        }
        return self::$instance;
    }
}