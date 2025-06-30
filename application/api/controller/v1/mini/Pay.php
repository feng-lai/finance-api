<?php
namespace app\api\controller\v1\mini;
use app\api\controller\v1\mini\Base;
use app\api\model\Order as OrderModel;
use app\api\model\OrderRenew as OrderRenewModel;
use app\api\model\User as UserModel;
use app\api\model\Places as PlacesModel;
use app\api\model\Notify as NotifyModel;
use app\api\model\SystemBill as SystemBillModel;
use app\api\logic\mini\Pay as PayLogic;
use think\facade\Env;

class Pay extends Base
{
    protected $noCheckToken = ['reback','renew_reback'];
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function pay(){
        checkInputEmptyByExit(['uuid']);
        $logic = new PayLogic();
        $result = $logic->pay(input('uuid'));
        if ($result['status'] == 1) {
            return $this->apiResult('2000','成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function renew(){
        checkInputEmptyByExit(['uuid']);
        $logic = new PayLogic();
        $result = $logic->renew(input('uuid'));
        if ($result['status'] == 1) {
            return $this->apiResult('2000','成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 回调
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function reback(){
        $app = app('wechat.payment');

        $app->handlePaidNotify(function($message, $fail){
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order_sn = $message['out_trade_no'];
            $order = OrderModel::json(['order_time'])->where('order_sn',$order_sn)->find();

            if (!$order || $order->status != 0) { // 如果订单不存在 或者 订单已经支付过了
                return $this->apiResult('SUCCESS');
            }
            ///////////// <- 建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////
            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                    // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {

                    $app = app('wechat.payment');

                    $is_pay = $app->order->queryByOutTradeNumber($order_sn);

                    if($is_pay['trade_state'] == 'SUCCESS'){

                        //后台通知审核
                        NotifyModel::create([
                            'uuid'=>uuidCreate(),
                            'content'=> '用户'.UserModel::where('uuid',$order->user_uuid)->value('nickname').'支付订单需要审核'
                        ]);

                        OrderModel::where('order_sn',$order_sn)->update(['status'=>1,'pay_time'=>date('Y-m-d H:i:s',time())]);

                        //更新用户公司
                        UserModel::where('uuid',$order->user_uuid)->Update(['company'=>$order->company]);

                        //系统流水
                        SystemBillModel::insert(['uuid'=>uuidCreate(),'user_uuid'=>$order->user_uuid,'amount'=>$order->price,'type'=>0,'bill_sn'=>numberCreate(),'order_uuid'=>$order->uuid,'create_time'=>date('Y-m-d H:i:s',time())]);

                        //预约成功提示
                        //下单提醒
                        $app = app('wechat.mini_program');
                        $data = [
                            'template_id' => 'GYYo-oa6vLeSLTpuHVYAu0qLPsSLa1CMn9cqhHQ-IAA', // 所需下发的订阅模板id
                            'touser' => UserModel::where('uuid',$order->user_uuid)->value('openid'),     // 接收者（用户）的 openid
                            //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
                            'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                                'thing11' => [
                                    'value' => PlacesModel::where('uuid',$order->places_uuid)->value('name'), //订单信息
                                ],
                                'amount3' => [
                                    'value' => '￥'.round($order->price,2), //订单金额
                                ],
                                'time5' => [
                                    'value' => date('Y-m-d H:i:s',time()), //订单时间
                                ],
                                'thing4' => [
                                    'value' => '下单成功，请等待后台审核', //订单备注
                                ]
                            ],
                        ];
                        $app->subscribe_message->send($data);

                    }
                    return $this->apiResult('SUCCESS');
                }
            }
        });
    }

    /**
     * 回调
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function renew_reback(){
        $app = app('wechat.payment');

        $app->handlePaidNotify(function($message, $fail){
            // 使用通知里的 "微信支付订单号" 或者 "商户订单号" 去自己的数据库找到订单
            $order_sn = $message['out_trade_no'];
            $order = OrderRenewModel::where('order_sn',$order_sn)->find();

            if (!$order || $order->status != 0) { // 如果订单不存在 或者 订单已经支付过了
                return $this->apiResult('SUCCESS');
            }
            ///////////// <- 建议在这里调用微信的【订单查询】接口查一下该笔订单的情况，确认是已经支付 /////////////
            if ($message['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态
                // 用户是否支付成功
                if ($message['result_code'] === 'SUCCESS') {

                    $app = app('wechat.payment');

                    $is_pay = $app->order->queryByOutTradeNumber($order_sn);

                    if($is_pay['trade_state'] == 'SUCCESS'){

                        OrderRenewModel::where('order_sn',$order_sn)->update(['status'=>1,'pay_time'=>date('Y-m-d H:i:s',time())]);

                        //系统流水
                        SystemBillModel::insert(['uuid'=>uuidCreate(),'user_uuid'=>$order->user_uuid,'amount'=>$order->price,'type'=>2,'bill_sn'=>numberCreate(),'order_uuid'=>$order->order_uuid,'create_time'=>date('Y-m-d H:i:s',time())]);


                    }
                    return $this->apiResult('SUCCESS');
                }
            }
        });
    }
}
