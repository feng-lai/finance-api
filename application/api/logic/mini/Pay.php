<?php
namespace app\api\logic\mini;
use app\api\model\User as UserModel;
use app\api\model\Order as OrderModel;
use app\api\model\OrderRenew as OrderRenewModel;
use app\api\model\SystemBill as SystemBillModel;
use think\Db;


class Pay
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function pay($uuid){
        $order = OrderModel::json(['order_time'])->where('uuid',$uuid)->find();
        if (empty($order) || $order->status != 0 || $order->pay_type != 1) {
            return ['status'=>0,'msg'=>'该订单无法支付'];
        }

        //判断
        $order_time = $order->order_time;
        $reserved = OrderModel::field('order_time')->order('create_time desc')->json(['order_time'])->where([['places_uuid','=',$order->places_uuid],['status','in',[1,2,3]]])->select();
        foreach($reserved as $v){
            foreach ($v->order_time as $vol){
                foreach($order_time as $val){
                    if($val->from == $vol->from){
                        throw new \Exception("场地已有预约，请选择其他时间段");
                    }
                    //已预约的时间
                    $hour = date('H',strtotime($val->from));
                    if($hour == 14 && date('Y-m-d H:i',strtotime($vol->to)+7200) == $val->from){
                        //throw new \Exception("连续两场会议中间需要间隔1个时间段做为清洁时间");
                    }
                    if(date('Y-m-d H:i',strtotime($vol->to)+3600) == $val->to){
                        throw new \Exception("连续两场会议中间需要间隔1个时间段做为清洁时间");

                    }
                    if($hour == 9 && date('Y-m-d H:i',strtotime($vol->to)+3600*13) == $val->from){
                        //throw new \Exception("连续两场会议中间需要间隔1个时间段做为清洁时间");
                    }
                }
            }
        }

        $app = app('wechat.payment');
        $result = $app->order->unify([
            'body' => '场地预定费用',
            'out_trade_no' => $order->order_sn,
            'total_fee' => input('test') == 1?1*100:$order->price*100,
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => UserModel::where('uuid',input('user_uuid'))->value('openid'),
        ]);

        if($result['result_code'] != 'SUCCESS'){
            return ['status'=>0,'msg'=>$result['err_code_des']];
        }
        $data = [];
        $time = (string)time();
        $sign =  MD5('appId='.$result['appid'].'&nonceStr='.$result['nonce_str'].'&package=prepay_id='.$result['prepay_id'].'&signType=MD5&timeStamp='.$time.'&key='.config('wechat.payment')['default']['key']);
        $data['appId'] = $result['appid'];
        $data['timeStamp'] = $time;
        $data['signType'] = 'MD5';
        $data['nonceStr'] = $result['nonce_str'];
        $data['package'] = 'prepay_id='.$result['prepay_id'];
        $data['paySign'] = $sign;
        return ['status'=>1,'data'=>$data];
    }

    public function renew($uuid){
        $order = OrderRenewModel::where('uuid',$uuid)->find();
        if (empty($order) || $order->status != 0 || $order->pay_type != 1) {
            return ['status'=>0,'msg'=>'该订单无法支付'];
        }

        $app = app('wechat.payment');
        $result = $app->order->unify([
            'body' => '场地预定费用',
            'out_trade_no' => $order->order_sn,
            'total_fee' => input('test') == 1?1*100:$order->price*100,
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => UserModel::where('uuid',input('user_uuid'))->value('openid'),
            'notify_url'=>'https://jrpt-api.vanke.com/v1/mini/Pay/renewReback'
        ]);

        if($result['result_code'] != 'SUCCESS'){
            return ['status'=>0,'msg'=>$result['err_code_des']];
        }
        $data = [];
        $time = (string)time();
        $sign =  MD5('appId='.$result['appid'].'&nonceStr='.$result['nonce_str'].'&package=prepay_id='.$result['prepay_id'].'&signType=MD5&timeStamp='.$time.'&key='.config('wechat.payment')['default']['key']);
        $data['appId'] = $result['appid'];
        $data['timeStamp'] = $time;
        $data['signType'] = 'MD5';
        $data['nonceStr'] = $result['nonce_str'];
        $data['package'] = 'prepay_id='.$result['prepay_id'];
        $data['paySign'] = $sign;
        return ['status'=>1,'data'=>$data];
    }
}
