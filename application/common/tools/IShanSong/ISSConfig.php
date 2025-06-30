<?php
/**
 * Created by Terry.
 * User: Terry
 * Email: terr_exchange@outlook.com
 * Date: 2020/10/13
 * Time: 16:24
 */
namespace app\common\tools\IShanSong;

class ISSConfig
{
    private $env;

    private $clientId;

    private $shopId;

    private $appSecret;

    public function __construct(array $config)
    {
        foreach ($config as $key => $vl)
        {
            $this ->$key = $vl;
        }
    }

    public function getKey(string $key)
    {
        return $this -> $key;
    }
}