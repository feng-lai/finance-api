<?php
/**
 * Created by PhpStorm.
 * User: fio
 * Date: 2017/2/21
 * Time: 下午4:47
 */

namespace app\common\tools;

class wechatUtil
{
    public static function getAccessToken($appId,$secret,$code){
        if (empty($appId) || empty($secret) || empty($code)) {
            return false;
        }
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?"
            . "appid={$appId}&secret={$secret}&code={$code}&grant_type=authorization_code";

//        var_dump($url);die;
        $result = self::getCurl($url);
        return $result;

    }


    public static function getUserInfo($accessToken,$openId){
        //网页授权获取用户信息
        if (empty($accessToken) || empty($openId)) {
            return false;
        }
        $url = "https://api.weixin.qq.com/sns/userinfo?"
            . "access_token={$accessToken}&openid={$openId}";
        $result = self::getCurl($url);
        return $result;
    }
    public static function adminGetUserInfo($accessToken,$openId){
        //后端获取用户信息
        if (empty($accessToken) || empty($openId)) {
            return false;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?"
            . "access_token={$accessToken}&openid={$openId}";
        $result = self::getCurl($url);
        return $result;
    }


    public static function getPublicAccessToken($appId, $appSecret)
    {
        if (empty($appId) || empty($appSecret)) {
            return false;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token"
            . "?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
        $result = self::getCurl($url);
        return $result;
    }

    // get方法请求
    public static function getCurl($url)
    {
        try {
            $curlHandle = curl_init();
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($curlHandle);
            curl_close($curlHandle);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public  static function curl_post_ssl($url, $vars)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);//证书检查
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'pem');
            curl_setopt($ch, CURLOPT_SSLCERT, dirname(__FILE__) . '/wechatCert/apiclient_cert.pem');//证书路径
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'pem');
            curl_setopt($ch, CURLOPT_SSLKEY, dirname(__FILE__) . '/wechatCert/apiclient_key.pem');
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'pem');
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/wechatCert/rootca.pem');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
            $data = curl_exec($ch);
            curl_close($ch);
            return $data;
        } catch (\Exception $e) {
            return false;
        }

    }

    // post方法请求
    public static function postCurl($url, $postData)
    {
        try {
            $curlHandle = curl_init();
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curlHandle, CURLOPT_POST, 1);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $postData);
            $result = curl_exec($curlHandle);
            curl_close($curlHandle);
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function getRandomString($length = 6)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g",
            "h", "i", "j", "k", "l", "m", "n",
            "o", "p", "q", "r", "s", "t",
            "u", "v", "w", "x", "y", "z",
            "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N",
            "O", "P", "Q", "R", "S", "T",
            "U", "V", "W", "X", "Y", "Z",
            "0", "1", "2", "3", "4",
            "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $length; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }

    public static function toUrlParams($dataArray)
    {
        $buff = "";
        foreach ($dataArray as $k => $v) {
            if (strtolower($k) != "sign" && $v != "" && !is_array($v)) {
                $buff .= "{$k}={$v}&";
            }
        }
        unset($k);
        unset($v);

        $buff = trim($buff, "&");
        return $buff;
    }


    public static function makeSign($dataArray, $key)
    {
        //签名步骤一：按字典序排序参数
        ksort($dataArray);
        $string = self::toUrlParams($dataArray);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    public static function toXml($dataArray)
    {
        if (!is_array($dataArray) || count($dataArray) <= 0) {
//            throw new WxPayException("数组数据异常！");
            return false;
        }

        $xml = "<xml>";
        foreach ($dataArray as $key => $val) {
//            $xml .= "<{$key}>{$val}</{$key}>";
            if (is_numeric($val)) {
                $xml .= "<{$key}>{$val}</{$key}>";
            } else {
                $xml .= "<{$key}><![CDATA[{$val}]]></{$key}>";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    public static function xmlParser($data)
    {
        unset($vals);
        unset($parser);
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // 不转换为大写
        xml_parse_into_struct($parser, $data, $vals);
        xml_parser_free($parser);
        $result = [];
        foreach ($vals as $item) {
            if ($item['tag'] == 'xml') {
                continue;
            }
            $result[$item['tag']] = $item['value'];
        }
        return $result;
    }

    public static function log_file($content, $title = 'LOG', $filename = 'wc_log_file', $logPath)
    {
        try {
            $titleShow = (strlen($title) > 30) ? substr($title, 0, 27) . '...' : $title;
            $spaceNum = (66 - strlen($titleShow)) / 2;
            $titleShow = '=' . str_repeat(' ', intval($spaceNum)) . $titleShow . str_repeat(' ', ceil($spaceNum)) . '=';

            $time = date('Y-m-d H:i:s');
            $content = var_export($content, true);

            $logContent = <<<EOT
====================================================================
{$titleShow}
====================================================================
time:     {$time}
title:    {$title}
--------------------------------------------------------------------
content:  \n{$content}\n\n\n
EOT;

            $logName = $filename . date('Ymd') . '.log';
            if (!is_dir($logPath)) {
                mkdir($logPath);
            }
            $logFile = fopen($logPath . $logName, "a");
            fwrite($logFile, $logContent);
            fclose($logFile);
        } catch (\Exception $e) {
            // do nothing
        }
    }
}
