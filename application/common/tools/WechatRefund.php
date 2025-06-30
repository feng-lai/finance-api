<?php
namespace app\common\tools;

use app\api\model\WechatRefund as WechatRefundModel;
use app\common\wechat\Util;
use app\api\controller\Api;
class WechatRefund{
    //微信退款

    private function getWeChatRefundData($out_refund_no, $outTradeNo, $total_fee, $fee)
    {
        $config = config('config.wxpay');
        $appId = $config['appid'];
        $mchId = $config['mchid'];
        $payKey = $config['paykey'];

        $orderData = [
            'appid' => $appId, // 公众账号ID
            'mch_id' => $mchId, // 商户号
//            'op_user_id' => config('wechat.MchId'),// 商户号
            'nonce_str' => Util::getRandomString(32), // 随机字符串
            'sign' => '',
            'sign_type' => 'MD5',
            'out_trade_no' => $outTradeNo, // 商户系统内部订单号
            'out_refund_no' => $out_refund_no, // 商户系统内部退款单号
            'fee_type' => 'CNY',
            'total_fee' => $total_fee,//订单金额
            'refund_fee' => $fee,//退款金额
            'refund_account' => "REFUND_SOURCE_RECHARGE_FUNDS",//退款资金来源 默认为未结算资金 当前设置为余额资金
        ];
        $orderData['sign'] = Util::makeSign($orderData, $payKey);
        $result = Util::toXml($orderData);
        return $result;
    }

    /**
     * @author: Jason
     * @time: 2019年8月11日
     * description:退款
     * @param string $outRefundNo 退款单号
     * @param string $outTradeNo 支付单号
     * @param float $total_fee 支付订单总金额
     * @param float $fee 本次退款金额
     * @param float $refund_content 退款备注
     * @param string $type 退款类型
     * @return bool
     */
    public function refund($outRefundNo, $outTradeNo, $total_fee, $fee,$refund_content)
    {
        $xmlData = $this->getWeChatRefundData($outRefundNo, $outTradeNo, $total_fee * 100, $fee * 100);
        $result = Util::curl_post_ssl("https://api.mch.weixin.qq.com/secapi/pay/refund", $xmlData);
        if (empty($result)) {
            return ['status'=>0,'msg'=>'退款接口无返回值'];
        }
        $result = Util::xmlParser($result);
        if (empty($result)) {
            return ['status'=>0,'msg'=>'退款接口无法解析'];
        }
        if ($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            return ['status'=>0,'msg'=>'失败','data'=>$result];
        }
        return ['status'=>1,'msg'=>'成功','data'=>$result];
    }
}