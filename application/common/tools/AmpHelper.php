<?php
namespace app\common\tools;

use app\common\tools\aop\AopClient;
use app\common\tools\aop\request\AlipaySystemOauthTokenRequest;
use app\common\tools\aop\request\AlipayUserInfoShareRequest;

/**
 * 支付宝用户授权工具类
 */
class AmpHelper
{


    const API_DOMAIN = "https://openapi.alipay.com/gateway.do?";
    const API_METHOD_GENERATE_QR = 'alipay.open.app.qrcode.create';
    const API_METHOD_AUTH_TOKEN = 'alipay.system.oauth.token';
    const API_METHOD_GET_USER_INFO = 'alipay.user.info.share';

    const SIGN_TYPE_RSA2 = 'RSA2';
    const VERSION = '1.0';
    const FILE_CHARSET_UTF8 = "UTF-8";
    const FILE_CHARSET_GBK = "GBK";
    const RESPONSE_OUTER_NODE_QR = 'alipay_open_app_qrcode_create_response';
    const RESPONSE_OUTER_NODE_AUTH_TOKEN = 'alipay_system_oauth_token_response';
    const RESPONSE_OUTER_NODE_USER_INFO = 'alipay_user_info_share_response';
    const RESPONSE_OUTER_NODE_ERROR_RESPONSE = 'error_response';

    const STATUS_CODE_SUCCESS = 10000;
    const STATUS_CODE_EXCEPT = 20000;


    /**
     * 获取用户信息接口，根据token
     * @param $code 授权码
     * 通过授权码获取用户的信息
     */
    public static function getAmpUserInfoByAuthCode($code){
        $aop = new AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = config('alipay.AppID');
        $aop->rsaPrivateKey = config('alipay.rsaPrivateKey');
        $aop->alipayrsaPublicKey=config('alipay.alipayPublicKey');
//        var_dump($aop->alipayrsaPublicKey);die;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new AlipaySystemOauthTokenRequest();
        $request->setGrantType("authorization_code");
        $request->setCode($code);
        $request->setRefreshToken("201208134b203fe6c11548bcabd8da5bb087a83b");
        $result = $aop->execute( $request);
        $result=json_decode(json_encode($result),true);
//        var_dump($result);die;
        if(!empty($result['error_response'])){
            //授权失败
            $error['msg']=$result['error_response']['sub_msg'];
            return $error;

        }else{
            //授权成功
          if(!empty($result['alipay_system_oauth_token_response'])){
              //授权成功
              $result=$result['alipay_system_oauth_token_response'];
              if(!empty($result['user_id'])){
                //获取用户信息


                    return $result;
              }else{
                  //未获取用户id
                  $error['msg']="用户授权失败，请联系管理员";
                  return $error;
              }

          }else{
              $error['msg']="用户授权失败，请联系管理员";
              return $error;
              //授权失败
          }


        }


    }


    /**
     * 获取小程序token接口
     */
    public static function getAmpToken($code)
    {
//        $code="9ecb142513614dd193448a323907QX55";
        $aop = new AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = config('alipay.AppID');;
        $aop->rsaPrivateKey = config('alipay.rsaPrivateKey');
        $aop->alipayrsaPublicKey=config('alipay.alipayPublicKey');
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new AlipaySystemOauthTokenRequest();
        $request->setGrantType("authorization_code");
        $request->setCode($code);
        $request->setRefreshToken("201208134b203fe6c11548bcabd8da5bb087a83b");
        $result = $aop->execute ($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        return $result;

    }
    /**
     * 获取用户详细信息接口
     */
    public static function getUserInfo($accessToken)
    {
//        var_dump($accessToken);die;
        $aop = new AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = config('alipay.AppID');;
        $aop->rsaPrivateKey = config('alipay.rsaPrivateKey');
        $aop->alipayrsaPublicKey=config('alipay.alipayPublicKey');
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new AlipayUserInfoShareRequest();
        $result = $aop->execute ( $request , $accessToken);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
       var_dump($result);die;


    }






}
