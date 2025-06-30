<?php
namespace app\common\tools;

use app\common\tools\alipaySDK\AopClient;
use app\common\tools\alipaySDK\request\AlipayTradeAppPayRequest;
use app\common\tools\alipaySDK\request\AlipayTradeQueryRequest;
use think\Exception;
use think\Loader;

class alipayClient
{
    public $appId;
    public $gatewayUrl;
    public $rsaPrivateKey;
    public $rsaPublicKey;
    public $alipayPublicKey;
    public $notifyUrl;
    public $refundUrl;
    public $logPath;

    public function __construct($appId, $app_rsa_private_key, $app_rsa_public_key,$alipayPublicKey)
    {
        // Loader::import('alipay.aop.AopClient', EXTEND_PATH);
        // import('alipay.aop.AopClient', EXTEND_PATH);
        include('alipaySDK/AopClient.php');
        $this->setAppId($appId);
        $this->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $this->rsaPrivateKey = $app_rsa_private_key;
        $this->rsaPublicKey = $app_rsa_public_key;
        $this->alipayPublicKey = $alipayPublicKey;
        $this->refundUrl = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $this->logPath = LOG_PATH;
    }

    public function order($outTradeNo, $fee, $body,$subject,$passback_params="",$notifyUrl = "")
    {
//        $fee = 0.01;//todo 测试
        try{
            if(!empty($notifyUrl)){
                $this->notifyUrl = $notifyUrl;
            }
            $aop = new AopClient();
            $aop->gatewayUrl = $this->gatewayUrl;
            $aop->appId = $this->appId;
            $aop->rsaPrivateKey = $this->rsaPrivateKey;
            $aop->apiVersion = '1.0';
            $aop->signType = 'RSA2';
            $aop->format='json';
            $aop->alipayPublicKey = $this->alipayPublicKey;
            //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay

            $request = new AlipayTradeAppPayRequest();
            //SDK已经封装掉了公共参数，这里只需要传入业务参数
            $bizcontent = $this->getOrderData($outTradeNo, $fee, $body,$subject,$passback_params);
            $request->setNotifyUrl($this->notifyUrl);
            $request->setBizContent($bizcontent);
            //这里和普通的接口调用不同，使用的是sdkExecute
            $response = $aop->sdkExecute($request);
        }catch (Exception $exception){
            // return false;
            return ['status'=>0,'data'=>'下单失败'];
        }
        return ['status'=>1,'data'=>$response];
        // return $response;//就是orderString 可以直接给客户端请求，无需再做处理。
    }

    public function query($out_trade_no)
    {
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey= $this->alipayPublicKey;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayTradeQueryRequest();
        $request->setBizContent("{" .
            "\"out_trade_no\":\"{$out_trade_no}\"" .
            "  }");
        $result = $aop->execute ( $request);

        return objtoArray($result->alipay_trade_query_response);

    }

    public function refund($outTradeNo, $fee, $out_request_no,$refund_reason = "订单已取消")
    {
        $aop = new \AopClient;
        $aop->gatewayUrl = $this->gatewayUrl;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey = $this->rsaPrivateKey;
        $aop->format = "json";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = $this->rsaPublicKey;
        Loader::import('alipay.aop.request.AlipayTradeRefundRequest', EXTEND_PATH);

        $request = new \AlipayTradeRefundRequest();
        $bizcontent = $this->getRefundData($outTradeNo, $fee, $out_request_no,$refund_reason);
        $request->setNotifyUrl($this->notifyUrl);
        $request->setBizContent($bizcontent);
        $response = $aop->execute($request);
        $response = (array)$response;
        $result = (array)$response['alipay_trade_refund_response'];
        if (empty($result)) {
            return false;
        }
        if ($result['code'] != '10000' || $result['msg'] != 'Success') {
            return false;
        }
        return true;
        //return $result;//todo::退款返回详情
    }

    public function handleNotify($requestArray)
    {
        if (!isset($requestArray['notify_id']) || !isset($requestArray['trade_no'])) {
            log_file($requestArray, 'parse error', 'alipay');
            return false;
        }

        if ($requestArray['trade_status'] != 'TRADE_SUCCESS') {
            log_file($requestArray, 'return code error', 'alipay');
            return false;
        }

        if (empty($requestArray['total_amount'])|| $requestArray['total_amount'] != $requestArray['buyer_pay_amount']) {
            log_file($requestArray, 'amount error', 'alipay');
            return false;
        }

        $aop = new \AopClient;
        $aop->alipayPublicKey = $this->alipayPublicKey;
        $bool = $aop->rsaCheckV1($requestArray, ROOT_PATH."public/alipay_public_key.pem", "RSA2");
        if(!$bool){//签名验证失败
            log_file($requestArray, 'sign error', 'alipay');
            return false;
        }

        return $requestArray;
    }

    private function getOrderData($outTradeNo, $fee, $body,$subject,$passback_params)
    {
        $orderData = [
            'out_trade_no' => $outTradeNo, // 商户系统内部订单号
            'body' => $body,
            'subject' => $subject,
            'total_amount' => $fee,
            'timeout_express' => "30m",
            'product_code' => "QUICK_MSECURITY_PAY",
            'seller_id' =>""
        ];
        if(!empty($passback_params)){
            $orderData['passback_params'] = $passback_params;
        }
        $orderData = json_encode($orderData,JSON_UNESCAPED_UNICODE);
        return $orderData;
    }

    private function getRefundData($outTradeNo, $fee, $out_request_no,$refund_reason)
    {
        $orderData = [
            'out_trade_no' => $outTradeNo, // 商户系统内部订单号
            'out_request_no' => $out_request_no,
            'refund_reason' => $refund_reason,
            'refund_amount' => $fee,
        ];
        $orderData = json_encode($orderData,JSON_UNESCAPED_UNICODE);
        return $orderData;
    }

    public static function getRandomString($length = 6)
    {
        $chars = array(
            "a",
            "b",
            "c",
            "d",
            "e",
            "f",
            "g",
            "h",
            "i",
            "j",
            "k",
            "l",
            "m",
            "n",
            "o",
            "p",
            "q",
            "r",
            "s",
            "t",
            "u",
            "v",
            "w",
            "x",
            "y",
            "z",
            "A",
            "B",
            "C",
            "D",
            "E",
            "F",
            "G",
            "H",
            "I",
            "J",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "Q",
            "R",
            "S",
            "T",
            "U",
            "V",
            "W",
            "X",
            "Y",
            "Z",
            "0",
            "1",
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9"
        );
        $charsLen = count($chars) - 1;
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $length; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }

    public function setErrorInfo($message, $detail)
    {
        $this->errorMessage = $message;
        $this->errorDetail = $detail;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }
}
