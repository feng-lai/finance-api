<?php
/**
 * Created by Terry.
 * User: Terry
 * Email: terr_exchange@outlook.com
 * Date: 2020/10/13
 * Time: 16:23
 */

namespace app\common\tools\IShanSong;

use app\api\model\Config;
use think\Exception;

class Core
{
    private $devUrl = 'http://open.s.bingex.com';

    private $url = 'http://open.ishansong.com';

    private $env;

    private $clientId;

    private $appSecret;

    private $shopId;

    private $accessToken;

    public function __construct(ISSConfig $config)
    {

        $this -> env = $config->getKey('env');

        $this -> clientId = $config->getKey('clientId');

        $this -> shopId = $config->getKey('shopId');

        $this -> appSecret = $config->getKey('appSecret');
    }

    public function orderCalculate($params)
    {
        $url = $this->_getUrl().'/openapi/developer/v5/orderCalculate';
        $params = [
            'clientId' => $this->clientId,
            'accessToken' => self::_getToken(),
            'timestamp' => time(),
            'data' => json_encode([
                'cityName' => $params['cityName'],
                'sender' => [
                    'fromAddress' => $params['fromAddress'],
                    'fromLatitude' => $params['fromLatitude'],
                    'fromSenderName' => $params['fromSenderName'],
                    'fromLongitude' => $params['fromLongitude'],
                    'fromMobile' => $params['fromMobile'],

                ],
                'receiverList' => [
                    [
                        'toReceiverName' => $params['toReceiverName'],
                        'orderNo' => $params['orderNo'],
                        'toAddress' => $params['toAddress'],
                        'toLatitude' => $params['toLatitude'],
                        'toLongitude' => $params['toLongitude'],
                        'toMobile' => $params['toMobile'],
                        'goodType' => 10,
                        'weight' => $params['weight'],
                    ]
                ],
                'appointType' => 0,
            ])
        ];
//        var_dump($params)

        $params['sign'] = $this->_encrypt($params);
        $result = $this->_curl($params,$url);
        return $result;
    }

    public function orderPlace($params)
    {
        $url = $this->_getUrl().'/openapi/developer/v5/orderPlace';
        $params = [
            'clientId' => $this->clientId,
            'accessToken' => self::_getToken(),
            'timestamp' => time(),
            'data' => json_encode([
                'issOrderNo' => $params['orderNo'],
            ])
        ];
        $params['sign'] = $this->_encrypt($params);
        return $this->_curl($params,$url);
    }

    public function orderInfo($params)
    {
        $url = $this->_getUrl().'/openapi/developer/v5/orderInfo';
        $params = [
            'clientId' => $this->clientId,
            'accessToken' => self::_getToken(),
            'timestamp' => time(),
            'data' => json_encode([
                'issOrderNo' => $params['orderNo'],
                'thirdOrderNo' => $params['outOrderNo'],
            ])
        ];
        $params['sign'] = $this->_encrypt($params);
        $result = $this->_curl($params,$url);

        return $result;
    }

    public function courierInfo($issOrderNo)
    {
        $url = $this->_getUrl().'/openapi/developer/v5/courierInfo';
        $params = [
            'clientId' => $this->clientId,
            'accessToken' => self::_getToken(),
            'timestamp' => time(),
            'data' => json_encode([
                'issOrderNo' => $issOrderNo,
            ])
        ];
        $params['sign'] = $this->_encrypt($params);
        $result = $this->_curl($params,$url);

        return $result;
    }
    
    private function _encrypt($params)
    {
        ksort($params);

        $sign = '';
        foreach ($params as $key => $vl)
        {
            if(is_array($vl)) $vl = json_encode($vl);
            $sign .=$key.$vl;
        }

        return strtoupper(md5($this->appSecret .= $sign));
    }

    private function _curl(array $params,string $url)
    {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $params);
        $result = curl_exec($curlHandle);
        curl_close($curlHandle);

        $result = json_decode($result,true);
        if($result['status'] != 200)
        {
            throw new Exception('闪送服务请求失败：'.$result['msg']);
        }
        return $result;
    }

    private function _refreshToken()
    {
        $url = $this->_getUrl().'/openapi/oauth/refresh_token';
        $params = [
            'clientId' => $this->clientId,
            'timestamp' => time(),
            'data' => json_encode([
                'refreshToken' => Config::where('label','ishansong_refresh_token')->value('value')
            ])
        ];
        $params['sign'] = $this->_encrypt($params);
        $result = $this->_curl($params,$url);

        Config::where('label','ishansong_token')->update(['value'=>$result['data']['access_token']]);

    }

    private function _getToken()
    {
        return $this -> accessToken = Config::where('label','ishansong_token')->value('value');
    }

    private function _getUrl()
    {
        if($this -> env == 'testing') return $this -> devUrl;
        return $this -> url;
    }
}