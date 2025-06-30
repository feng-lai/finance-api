<?php
namespace app\common\tools;
use Exception;

class WechatLogin
{
    protected $appId;

    protected $appSecret;
    protected $accessToken;
    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

//
//    public function getAccessToken(){
//        //获取Accesstoken
//
//    }

    public function getInfo($ACCESS_TOKEN,$open_id){
        //获取用户信息
        $url = 'https://api.weixin.qq.com/sns/userinfo?' .
            'access_token=' . $ACCESS_TOKEN .
            '&openid=' . $open_id .
            '&lang=zh_CN';
        $res=curl_send($url);
        $user_info = json_decode($res,true);
            $where['open_id'] = $user_info['openid'];
            $info = access_token($this->getAppId(),$this->getAppSecret());
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$info->access_token}&openid={$where['open_id']}&lang=zh_CN";
            $res=curl_send($url);
            $jsonArray = json_decode($res);
            return $jsonArray;
    }

    public function sendTemplateMessage($openId, $templateId, $dataArray, $hrefUrl = '', $topColor = '')
    {
        if (empty($this->getAccessToken())) {
            return false;
        }
        if (empty($openId) || empty($templateId) || !is_array($dataArray)) {
            return false;
        }

        # build url
        $host = 'https://api.weixin.qq.com/cgi-bin/message/template/send';
        $params = [
            'access_token' => $this->getAccessToken(),
        ];
        $url = $host . '?' . http_build_query($params);

        # build post data
        $postData = [
            'touser' => $openId,
            'template_id' => $templateId,
        ];
        if (!empty($hrefUrl)) {
            $postData['url'] = $hrefUrl;
        }
        if (!empty($topColor)) {
            $postData['topcolor'] = $topColor;
        }
        $postData['data'] = $dataArray;

        $result = $this->postCurl($url, json_encode($postData, JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            return false;
        }
        $result = json_decode($result, true);
        if (!is_array($result)) {
            return false;
        }
        if (!isset($result['errcode'])) {
            return false;
        }
        $errcode = $result['errcode'];
        $errmsg = $result['errmsg'];
        $msgId = $result['msgid'];
        if (!empty($errcode)) {
            return false;
        }
        return $msgId;
    }

    public function getMediaFile($mediaId, $mediaFilePath)
    {
        if (empty($this->getAccessToken())) {
            return false;
        }

        $host = 'https://api.weixin.qq.com/cgi-bin/media/get';
        $params = [
            'access_token' => $this->getAccessToken(),
            'media_id' => $mediaId,
        ];
        $url = $host . '?' . http_build_query($params);

        $fileName = self::downloadFileByCurl($url, $mediaFilePath);

        if (empty($fileName)) {
            return false;
        }
        return $fileName;
    }

    /**
     * @return mixed
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param mixed $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return mixed
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * @param mixed $appSecret
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        if (!empty($this->accessToken)) {
            return $this->accessToken;
        }

        # get accessToken from cache
        $accessToken = Cache::get('wechat_accessToken');
        if (!empty($accessToken)) {
            $this->setAccessToken($accessToken);
            return $this->accessToken;
        }

        $accessToken = $this->buildAccessToken();
        $this->setAccessToken($accessToken);
        return $this->accessToken;
    }

    /**
     * @param mixed $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }
            /*
            * @result
            */
    public function buildAccessToken($code)
    {
        var_dump($code);
        var_dump($this->appId);
        var_dump($this->appSecret);
        if (empty($this->appId) || empty($this->appSecret)||empty($code)) {
            return false;
        }

        $host = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $params = [

            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => "authorization_code",
        ];
        $url = $host . '?' . http_build_query($params);
//        var_dump($url);die;
        $result = self::getCurl($url);
            var_dump($result);die;
        if (empty($result)) {
            return false;
        }
        $result = @json_decode($result, true);
        if (empty($result)) {
            return false;
        }

        if (!isset($result['access_token']) || !isset($result['expires_in'])) {
            return false;
        }
        return $result;
    }

//    private function saveTokenInCache($accessToken, $expiresIn)
//    {
//        return Cache::set('wechat_accessToken', $accessToken, $expiresIn);
//    }

    public function getCurl($url)
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
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
    }

    public function postCurl($url, $postData)
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
        } catch (Exception $e) {
            $e->getMessage();
            return false;
        }
    }

    public function downloadFileByCurl($url, $savePath)
    {
        try {
            $curlHandle = curl_init();
            curl_setopt($curlHandle, CURLOPT_URL, $url);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlHandle, CURLOPT_HEADER, true);
            curl_setopt($curlHandle, CURLOPT_NOBODY, false);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($curlHandle);

            $header = '';
            $body = '';
            if (curl_getinfo($curlHandle, CURLINFO_HTTP_CODE) == '200') {
                $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE); //头信息size
                $header = substr($result, 0, $headerSize);
                $body = substr($result, $headerSize);
            }
            curl_close($curlHandle);

            $arr = array();
            if (preg_match('/filename="(.*?)"/', $header, $arr)) {
                $fileName = $arr[1];
                $fullName = rtrim($savePath, '/') . '/' . $fileName;

                //创建目录并设置权限
                $basePath = dirname($fullName);
                if (!file_exists($basePath)) {
                    @mkdir($basePath, 0755, true);
                }
                if (file_put_contents($fullName, $body)) {
                    return $fileName;
                }
            } else {
                $body = @json_decode($body, true);
                if (is_array($body) && isset($body['errcode']) && $body['errcode'] == 40001) {
                    $this->accessToken = $this->buildAccessToken();
                    return $this->downloadFileByCurl($url, $savePath);
                }
            }
            return false;
        } catch (Exception $e) {
            $e->getMessage();
            log_file($e->getMessage(), 'download', 'wechat');
            return false;
        }
    }




}