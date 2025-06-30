<?php
namespace app\common\tools;

use Alipay\aop\AopClient;
use Alipay\aop\request\AlipayTradeRefundRequest;
// use app\common\tools\alipaySDK\AopClient;
// use app\common\tools\alipaySDK\request\AlipayTradeRefundRequest;

class AliRefund{
    //支付宝退款


    /**
     * @author: jason
     * @time: 2019年10月1、9日
     * description:退款
     * @param string $outRefundNo 退款单号
     * @param string $outTradeNo 支付单号
     * @param float $total_fee 支付订单总金额
     * @param float $fee 本次退款金额
     * @return bool
     */
    public function refund($outRefundNo, $outTradeNo, $total_fee, $fee,$refund_content)
    {
        $config = config('config.alipay');
        $aop = new AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $config['appId'];
        $aop->rsaPrivateKey = $config['rsaPrivateKey'];
        $aop->alipayrsaPublicKey = $config['alipayrsaPublicKey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='utf-8';
        $aop->format='json';
        $request = new AlipayTradeRefundRequest();

        $info = json_encode(['out_trade_no'=>$outRefundNo,'trade_no'=>$outTradeNo,'refund_amount'=>$fee,
            'refund_reason'=>'订单取消退款'],JSON_UNESCAPED_UNICODE);
        $request->setBizContent($info);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $result = $aop->execute($request);
        $result=json_decode(json_encode($result),true);

        if (empty($result)) {
            return ['status'=>0,'msg'=>'退款接口无法解析'];
        }
        if ($result['alipay_trade_refund_response']['code'] != '10000') {
            return ['status'=>0,'msg'=>$result['alipay_trade_refund_response']['sub_msg'],'data'=>$result];
        }
        return ['status'=>1,'msg'=>'成功','data'=>$result,'data'=>$result];
    }
}