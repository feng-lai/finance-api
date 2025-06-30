<?php
/**
 * Created by PhpStorm.
 * User: fio
 */

namespace app\common\wechat;

use think\Exception;
use think\exception\HttpException;

class Pay
{
    private $appId = 'wxf5a946c9ae7c8643';
    private $mchId = '1603831476';
    private $appPayKey = 'aaabbbcccdddqwerasd123456789asda';
    public $notifyUrl = 'http://112.74.113.0/delipy/index.php/Admin/Index/callback';
    public $unifiedOrderUrl = '';
    public $notifyUrl_recharge = '';
    public $refundUrl = '';
    public $errorMessage = '';
    public $errorDetail;
    public $logPath = '';
    public $orderQueryUrl = '';

    public function __construct($appId, $mchId, $payKey)
    {
        $this->setAppId($appId);
        $this->setMchId($mchId);
        $this->setAppPayKey($payKey);
//        $this->setSubAppId($sub_appId);
//        $this->setSubMchId($sub_MchId);
        $this->notifyUrl_recharge =  '';
        $this->unifiedOrderUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $this->refundUrl = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $this->orderQueryUrl = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $this->logPath = LOG_PATH;
    }

	/**
	 * @param string $outTradeNo 订单号
	 * @param number $fee 订单金额
	 * @param string $userOpenId 用户openid 在h5支付中不使用
	 * @param string $body 订单body
	 * @param string $attach 订单携带标识
	 * @param string $pay_type 支付类型 - 1 h5支付 2公众号支付or小程序支付 3app支付
	 * @param string $notifyUrl 支付callback 地址
	 * @return array|bool
	 */
    public function order($outTradeNo, $fee, $userOpenId='', $body, $attach = '',$pay_type,$notifyUrl)
    {
        # 发送统一下单请求
        $xmlData = $this->getWeChatOrderData($outTradeNo, $fee * 100, $userOpenId, $body, $attach,$pay_type,$notifyUrl);
        // dump($xmlData);die;
        $result = Util::postCurl($this->unifiedOrderUrl, $xmlData);
        if (empty($result)) {
            // $this->setErrorInfo('下单失败', '请求订单失败');
            // return false;
            return ['status'=>0,'msg'=>'下单失败', '请求订单失败'];
        }
        $result = Util::xmlParser($result);
        if (empty($result)) {
            // $this->setErrorInfo('下单失败', '订单解析失败');
            // return false;
            return ['status'=>0,'msg'=>'下单失败', '订单解析失败'];
        }
        if ($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            // throw new HttpException(404,$result['err_code_des'] ?? '统一下单接口异常');
            return ['status'=>0,'msg'=>$result['err_code_des'] ?? '统一下单接口异常'];
        }

        if($pay_type == 3)
        {
            //$requestData['partnerId'] =$this->mchId;
            //$requestData['prepayId'] =$result['prepay_id'];
            //$requestData['package'] ='Sign=WXPay';

            $requestData = [
                'appid' => $this->appId,
                'timestamp' => time(),
                'noncestr' => Util::getRandomString(32),
                'prepayid' => $result['prepay_id'],
                'partnerid' => $this->mchId,
                'package' => 'Sign=WXPay',
                'paySign' => '',
                'Sign' => '',
            ];
            $requestData['paySign'] = Util::makeSign($requestData, $this->appPayKey);
            $requestData['Sign'] = $requestData['paySign'];
            return ['status'=>1,'msg'=>'下单成功','data'=>$requestData];
        }else{
            $requestData = [
                'appId' => $this->appId,
                'timeStamp' => time(),
                'nonceStr' => Util::getRandomString(32),
                'package' => 'prepay_id=' . $result['prepay_id'],
                'signType' => 'MD5',
                'paySign' => '',
                'Sign' => '',
            ];
        }

        if(!empty($result['code_url'])){
            $requestData['code_url'] = $result['code_url'];
        }
        if(!empty($result['mweb_url'])){
            $requestData['mweb_url'] = $result['mweb_url'];
        }

        $requestData['paySign'] = Util::makeSign($requestData, $this->appPayKey);
        $requestData['Sign'] = $requestData['paySign'];
        // return $requestData;
        return ['status'=>1,'msg'=>'下单成功','data'=>$requestData];
    }

    public function query($out_trade_no)
    {
        $data = [
            'appid' => $this->appId, // 公众账号ID
            'mch_id' => $this->mchId, // 商户号
            'out_trade_no' => $out_trade_no,
            'nonce_str' => Util::getRandomString(32), // 随机字符串
            'sign' => '',
        ];
        $data['sign'] = Util::makeSign($data, $this->appPayKey);
        $xml = Util::toXml($data);

        $response = Util::postCurl($this->orderQueryUrl, $xml);
        $response = Util::xmlParser($response);

        if($response['return_code'] != 'SUCCESS' ||$response['result_code'] != 'SUCCESS')
        {
            throw new HttpException(404,$response['err_code_des'] ?? '查询订单接口异常');
        }

        return $response;

    }


    public function refund($outRefundNo, $outTradeNo, $fee,$refund_fee)
    {
        $xmlData = $this->getWeChatRefundData($outRefundNo, $outTradeNo, $fee * 100,$refund_fee* 100);
        $result = Util::curl_post_ssl($this->refundUrl, $xmlData);
        $result = Util::xmlParser($result);
        if (empty($result)) {
            $this->setErrorInfo('退款失败', '请求退款失败');
            return false;
        }

        if (empty($result)) {
            $this->setErrorInfo('退款失败', '退款单解析失败');
            throw new Exception('退款失败退款单解析失败');
        }
        if ($result['return_code'] != 'SUCCESS') {
            $this->setErrorInfo('退款失败', $result['return_msg']);
            throw new Exception('退款失败'.$result['return_msg']);
        }
        if ($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            $this->setErrorInfo('下单失败', $result['return_msg']);
            throw new Exception('退款失败'.$result['return_msg']);

        }
        return true;
    }

    public function handleNotify($requestStr)
    {
        $requestArray = Util::xmlParser($requestStr);

        if (empty($requestArray) || !isset($requestArray['return_code'])) {
            Util::log_file($requestArray, 'parse error', 'wxpay', $this->logPath);
            return false;
        }
        if ($requestArray['return_code'] != 'SUCCESS') {
            Util::log_file($requestArray, 'return code error', 'wxpay', $this->logPath);
            return false;
        }

        $realSign = Util::makeSign($requestArray, $this->appPayKey);

        if ($realSign != $requestArray['sign']) {
            // sign fail
            Util::log_file($requestArray, 'sign fail:' . $realSign, 'wxpay', $this->logPath);
            return false;
        }

        if ($requestArray['result_code'] != 'SUCCESS') {
            // pay fail
            Util::log_file($requestArray, 'result is fail', 'wxpay', $this->logPath);
            return false;
        }

        return $requestArray;
    }

    private function getWeChatOrderData($outTradeNo, $fee, $userOpenId, $body, $attach,$pay_type,$notifyUrl)
    {
    	//微信支付
		//根据支付类型 组装不同的orderData
		if($pay_type=="1"){
			//h5支付
			$orderData = [
				'appid' => $this->appId, // 公众账号ID
				'mch_id' => $this->mchId, // 商户号
				'device_info' => 'WEB',
				'nonce_str' => Util::getRandomString(32), // 随机字符串
				'sign' => '',
				'body' => $body,
				'attach' => '' . $attach, // 自定义参数
				'out_trade_no' => $outTradeNo, // 商户系统内部订单号
				'total_fee' => $fee,
				'spbill_create_ip' => request()->ip(),
				'notify_url' => $notifyUrl,
				'trade_type' => 'MWEB',
				'scene_info' => '{"h5_info":{"type": "Wap","wap_url": "gymoo-h5.gymoo.cn","wap_name": "积木基础模块"}}'
			];


		}elseif($pay_type=="2"){
			//公众号支付or小程序支付-需要传递openid
            $orderData = [
                'appid' => $this->appId, // 公众账号ID
                'mch_id' => $this->mchId, // 商户号
                'device_info' => 'APP',
                'nonce_str' => Util::getRandomString(32), // 随机字符串
                'sign' => '',
                'sign_type' => 'MD5',
                'body' => $body,
                'attach' => '' . $attach, // 自定义参数
                'out_trade_no' => $outTradeNo, // 商户系统内部订单号
                'fee_type' => 'CNY',
                'total_fee' => $fee,
                'spbill_create_ip' => request()->ip(),
                'notify_url' => $notifyUrl,
                'trade_type' => 'JSAPI',
                'openid' => $userOpenId,
            ];
            // dump($orderData);die;


		}elseif($pay_type=="3"){
			//app支付
//            $orderData = [
//                'appid' => $this->appId, // 公众账号ID
//                'mch_id' => $this->mchId, // 商户号
//                'nonce_str' => Util::getRandomString(32), // 随机字符串
//                'sign' => '',
//                'body' => $body,
//                'attach' => '' . $attach, // 自定义参数
//                'out_trade_no' => $outTradeNo, // 商户系统内部订单号
//                'total_fee' => $fee,
//                'spbill_create_ip' => request()->ip(),
//                'notify_url' => $notifyUrl,
//                'trade_type' => 'APP',
//            ];
            $orderData = [
                'appid' => $this->appId, // 开放平台appid
                'mch_id' => $this->mchId, // 商户号
                'nonce_str' => Util::getRandomString(32), // 随机字符串
                'sign' => '',
                'body' => $body,
                'attach' => '' . $attach, // 自定义参数
                'out_trade_no' => $outTradeNo, // 商户系统内部订单号
                'fee_type' => 'CNY',
                'total_fee' => $fee,
                'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
                'notify_url' => $notifyUrl,
                'trade_type' => 'APP',

            ];
            //var_dump($orderData);die;
		}elseif($pay_type=="4"){
		    //扫码支付
            $orderData = [
                'appid' => $this->appId, // 公众账号ID
                'mch_id' => $this->mchId, // 商户号
                'device_info' => 'WEB',
                'nonce_str' => Util::getRandomString(32), // 随机字符串
                'sign' => '',
                'body' => $body,
                'attach' => '' . $attach, // 自定义参数
                'out_trade_no' => $outTradeNo, // 商户系统内部订单号
                'total_fee' => $fee,
                'spbill_create_ip' => request()->ip(),
                'notify_url' => $notifyUrl,
                'trade_type' => 'NATIVE',
            ];



		}else{

            return false;

        }
		//服务商模式支付
//        $orderData = [
//            'appid' => $this->appId, // 公众账号ID
//            'mch_id' => $this->mchId, // 商户号
//            'sub_appid' => $this->appSubAppId, // 子商户号appid
//            'sub_mch_id' => $this->subMchId, // 子商户号
//            'device_info' => 'APP',
//            'nonce_str' => Util::getRandomString(32), // 随机字符串
//            'sign' => '',
//            'sign_type' => 'MD5',
//            'body' => $body,
//            'attach' => '' . $attach, // 自定义参数
//            'out_trade_no' => $outTradeNo, // 商户系统内部订单号
//            'fee_type' => 'CNY',
//            'total_fee' => $fee,
//            'spbill_create_ip' => request()->ip(),
//            'notify_url' => $this->notifyUrl_recharge,
//            'trade_type' => 'JSAPI',
//            'sub_openid' => $userOpenId,
//        ];
//

        if ($orderData['spbill_create_ip'] == '::1') {
            $orderData['spbill_create_ip'] = '127.0.0.1';
        }
//        var_dump($orderData);die;
        $orderData['sign'] = Util::makeSign($orderData, $this->appPayKey);
        $result = Util::toXml($orderData);
//        var_dump($result);die;
        return $result;
    }

    private function app_getWeChatOrder($outTradeNo, $fee, $body, $attach)
    {
        //appid
        $orderData = [
            'appid' => $this->openappId, // 开放平台appid
            'mch_id' => $this->mchId, // 商户号
            'nonce_str' => Util::getRandomString(32), // 随机字符串
            'sign' => '',
            'body' => $body,
            'attach' => '' . $attach, // 自定义参数
            'out_trade_no' => $outTradeNo, // 商户系统内部订单号
            'fee_type' => 'CNY',
            'total_fee' => $fee,
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
            'notify_url' => $this->notifyUrlOrderPay,
            'trade_type' => 'APP',

        ];
//        var_dump($orderData);die;
        if ($orderData['spbill_create_ip'] == '::1') {
            $orderData['spbill_create_ip'] = '127.0.0.1';
        }
        $orderData['sign'] = Util::makeSign($orderData, $this->appPayKey);
        $result = Util::toXml($orderData);
        return $result;
    }

    private function getWeChatRefundData($out_refund_no, $outTradeNo, $fee,$refund_fee)
    {
        $orderData = [
            'appid' => $this->appId, // 公众账号ID
            'mch_id' => $this->mchId, // 商户号
            'op_user_id' => $this->mchId,// 商户号
            'nonce_str' => Util::getRandomString(32), // 随机字符串
            'sign' => '',
            'sign_type' => 'MD5',
            'out_trade_no' => $outTradeNo, // 商户系统内部订单号
            'out_refund_no' => $out_refund_no, // 商户系统内部退款单号
            'fee_type' => 'CNY',
            'total_fee' => $fee,//订单金额
            'refund_fee' => $refund_fee,//退款金额
            'refund_account' => "REFUND_SOURCE_RECHARGE_FUNDS",//退款资金来源 默认为未结算资金 当前设置为余额资金
        ];
        $orderData['sign'] = Util::makeSign($orderData, $this->appPayKey);
        $result = Util::toXml($orderData);
        return $result;
    }


    public function setErrorInfo($message, $detail)
    {
        $this->errorMessage = $message;
        $this->errorDetail = $detail;
    }

    /**
     * @param string $notifyUrl
     */
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }
    public function setnotifyUrl_recharge($notifyUrl_recharge)
    {
        $this->notifyUrl_recharge = $notifyUrl_recharge;
    }


    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @param string $mchId
     */
    public function setMchId($mchId)
    {
        $this->mchId = $mchId;
    }

    /**
     * @param string $appPayKey
     */
    public function setAppPayKey($appPayKey)
    {
        $this->appPayKey = $appPayKey;
    }
//    public function setSubAppId($appSubAppId)
//    {
//        $this->appSubAppId = $appSubAppId;
//    }
//    public function setSubMchId($subMchId)
//    {
//        $this->subMchId = $subMchId;
//    }

}


class WxPayDataBase
{
    protected $values = array();

    /**
     * 设置签名，详见签名生成算法类型
     * @param string $value
     **/
    public function SetSignType($sign_type)
    {
        $this->values['sign_type'] = $sign_type;
        return $sign_type;
    }

    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function SetSign($config)
    {
        $sign = $this->MakeSign($config);
        $this->values['sign'] = $sign;
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return 值
     **/
    public function GetSign()
    {
        return $this->values['sign'];
    }

    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     **/
    public function IsSignSet()
    {
        return array_key_exists('sign', $this->values);
    }

    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    public function ToXml()
    {
        if(!is_array($this->values) || count($this->values) <= 0)
        {
            throw new WxPayException("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    public function FromXml($xml)
    {
        if(!$xml){
            throw new WxPayException("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @param WxPayConfigInterface $config  配置对象
     * @param bool $needSignType  是否需要补signtype
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign($config, $needSignType = true)
    {
        if($needSignType) {
            $this->SetSignType($config->GetSignType());
        }
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$config->GetKey();

        //签名步骤三：MD5加密或者HMAC-SHA256
        if($config->GetSignType() == "MD5"){
            $string = md5($string);
        } else if($config->GetSignType() == "HMAC-SHA256") {
            $string = hash_hmac("sha256",$string ,$config->GetKey());
        } else {
            throw new WxPayException("签名类型不支持！");
        }

        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);

        return $result;
    }

    /**
     * 获取设置的值
     */
    public function GetValues()
    {
        return $this->values;
    }
}
/**
 *
 * 提交JSAPI输入对象
 * @author widyhu
 *
 */
class WxPayAppPay extends WxPayDataBase
{
    /**
     * 设置微信分配的APPID
     * @param string $value
     **/
    public function SetAppid($value)
    {
        $this->values['appid'] = $value;
    }
    /**
     * 设置partner id
     * @param string $value
     **/
    public function SetPartnerId($value)
    {
        $this->values['partnerid'] = $value;
    }
    /**
     * 获取微信分配的公众账号ID的值
     * @param string $value
     **/
    public function SetPrepayId($value)
    {
        $this->values['prepayid'] = $value;
    }

    /**
     * 设置支付时间戳
     * @param string $value
     **/
    public function SetTimeStamp($value)
    {
        $this->values['timestamp'] = $value;
    }
    /**
     * 获取支付时间戳的值
     * @return 值
     **/
    public function GetTimeStamp()
    {
        return $this->values['timeStamp'];
    }
    /**
     * 判断支付时间戳是否存在
     * @return true 或 false
     **/
    public function IsTimeStampSet()
    {
        return array_key_exists('timeStamp', $this->values);
    }

    /**
     * 随机字符串
     * @param string $value
     **/
    public function SetNonceStr($value)
    {
        $this->values['noncestr'] = $value;
    }
    /**
     * 获取notify随机字符串值
     * @return 值
     **/
    public function GetReturn_code()
    {
        return $this->values['nonceStr'];
    }
    /**
     * 判断随机字符串是否存在
     * @return true 或 false
     **/
    public function IsReturn_codeSet()
    {
        return array_key_exists('nonceStr', $this->values);
    }


    /**
     * 设置订单详情扩展字符串
     * @param string $value
     **/
    public function SetPackage($value)
    {
        $this->values['package'] = $value;
    }
    /**
     * 获取订单详情扩展字符串的值
     * @return 值
     **/
    public function GetPackage()
    {
        return $this->values['package'];
    }
    /**
     * 判断订单详情扩展字符串是否存在
     * @return true 或 false
     **/
    public function IsPackageSet()
    {
        return array_key_exists('package', $this->values);
    }

    /**
     * 判断签名方式是否存在
     * @return true 或 false
     **/
    public function IsSignTypeSet()
    {
        return array_key_exists('signType', $this->values);
    }

    /**
     * 设置签名方式
     * @param string $value
     **/
    public function SetSign($value)
    {
        $this->values['sign'] = $value;
    }
    /**
     * 获取签名方式
     * @return 值
     **/
    public function GetPaySign()
    {
        return $this->values['paySign'];
    }
    /**
     * 判断签名方式是否存在
     * @return true 或 false
     **/
    public function IsPaySignSet()
    {
        return array_key_exists('paySign', $this->values);
    }
}