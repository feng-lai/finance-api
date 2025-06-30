<?php
/**
 * Created by PhpStorm.
 * User: Airon
 * Date: 2016/11/17
 * Time: 17:21
 *
 */
namespace app\common\tools;

use think\Cache;
use think\Db;
use think\Exception;

class Easemob
{
    const HTTP_GET = 0;
    const  HTTP_POST = 1;

    /*
     * 注册用户
     */
    public function regChatUser($type,$uid, $nick_name)
    {
        $token = $this->getToken();
        if ($token == '') {
            return '';
        }
        $config = config('easemob');
//        var_dump($config);die;
        $url = $config['huanxin_domain'] . '/' . $config['orgName'] . '/' . $config['appName'] . '/users';
        $requestHeaders = array();
        $requestBody['username'] = $type.$uid.getRandChar(4);
        $requestBody['password'] = $config['password'];
        $requestBody['nickname'] = $nick_name;
        $account = $requestBody;
        $requestBody['grant_type'] = 'client_credentials';
        $requestBody['client_id'] = $config['client_id'];
        $requestBody['client_secret'] = $config['client_secret'];
        $requestBody = json_encode($requestBody);
        $response = $this->sendRequest(self::HTTP_POST, $url, $requestHeaders, $requestBody);
        $response = json_decode($response, true);
        if (!empty($response['entities'][0]['uuid'])) {
            //成功
            return $account;
        }else{
            log_file($response,"easemobError","easemob");
        }
        /*
   array(9) {
  ["action"] => string(4) "post"
  ["application"] => string(36) "890087e0-8bf2-11e8-8351-ffb8009edef8"
  ["path"] => string(6) "/users"
  ["uri"] => string(54) "https://a1.easemob.com/1167180719177591/cheersto/users"
  ["entities"] => array(1) {
    [0] => array(9) {
      ["uuid"] => string(36) "e484e210-93f3-11e8-a964-81c8d1989ff0"
      ["type"] => string(4) "user"
      ["created"] => int(1532953635505)
      ["modified"] => int(1532953635505)
      ["username"] => string(18) "partner15989323465"
      ["activated"] => bool(true)
      ["client_id"] => string(26) "YXA6iQCH4IvyEeiDUf-4AJ7e-A"
      ["client_secret"] => string(31) "YXA6pjtCnWU-SSoIQtU8Bk_l1aduCYc"
      ["grant_type"] => string(18) "client_credentials"
    }
  }
  ["timestamp"] => int(1532953635507)
  ["duration"] => int(0)
  ["organization"] => string(16) "1167180719177591"
  ["applicationName"] => string(8) "cheersto"
}
         */
//        try {
            if (!empty($response['entities'][0]['client_id'])) {
                return $account;
            } else {
                if (strstr($response["error"], "duplicate")) {
                    return $account;
                } else {
                    return false;
                }
            }
//        } catch (Exception $e) {
//            return false;
//        }
    }

    /**获取环信token
     * @return string token
     */
    public function getToken()
    {
        if(Cache::get('easemob_access_token')){
            return Cache::get('easemob_access_token');
        }
        $config = config('easemob');
        $url = $config['huanxin_domain'] . '/' . $config['orgName'] . '/' . $config['appName'] . '/token';
        $requestHeaders = array('Content-Type: application/json');
        $requestBody = array();
        $requestBody['grant_type'] = 'client_credentials';
        $requestBody['client_id'] = $config['client_id'];
        $requestBody['client_secret'] = $config['client_secret'];
        $requestBody = json_encode($requestBody);
        $response = $this->sendRequest(self::HTTP_POST, $url, $requestHeaders, $requestBody);
        $response = json_decode($response);
        try {
            $token = $response->access_token;
            Cache::set('easemob_access_token',$token,3600);
            return $token;
        } catch (Exception $e) {
            return '';
        }
    }
    public function createGroup( $groupName)
    {
        $config = config('easemob');
        $token = $this->getToken();
        $url = $config['huanxin_domain'] . '/' . $config['orgName'] . '/' . $config['appName'] . '/chatgroups';
        $requestHeaders = [
            "Content-Type: application/json",
            "Authorization:Bearer {$token}"
        ];
        $requestBody = [
            'groupname' => 'inspection_' . $groupName,
            'desc' => 'inspection_' . $groupName,
            'public' => true,
            'maxusers' => 2000,
            'approval' => false,
            'owner' => $config['admin_username'],
        ];
        $requestBody = json_encode($requestBody);
        $response = $this->sendRequest(self::HTTP_POST, $url, $requestHeaders, $requestBody);
        $response = json_decode($response, true);
        if (isset($response['data']) && isset($response['data']['groupid']) && !empty($response['data']['groupid'])) {
            return $response['data']['groupid'];
        } else {
            return false;
        }
    }

    public function sendRequest($method, $url, array $requestHeader, $requestBody)
    {
        $ch = curl_init($url);
        if ($method == self::HTTP_GET) {
            curl_setopt_array($ch, array(
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $requestHeader,
            ));
        } elseif ($method == self::HTTP_POST) {
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $requestHeader,
                CURLOPT_POSTFIELDS => $requestBody
            ));
        }
        // Send the request
        $response = curl_exec($ch);
        // Check for errors
        if ($response === false) {
            die(curl_error($ch));
        }

        return $response;
    }

}