<?php
namespace app\api\logic\mini;
use app\api\model\Order as OrderModel;
use app\api\model\PayNumbers as PayNumbersModel;
use app\api\model\SystemBill;
use app\api\model\User as UserModel;
use app\api\model\OrderRefund as OrderRefundModel;
use app\api\model\Config as ConfigModel;
use app\api\model\Places as PlacesModel;
use app\api\model\SystemBill as SystemBillModel;
use app\api\model\Notify as NotifyModel;
use app\api\model\OrderChange as OrderChangeModel;
use app\api\model\OrderRenew as OrderRenewModel;

use app\common\wechat\Pay;
use app\common\tools\JPush;

use Alipay\aop\AopClient;
use Alipay\aop\request\AlipayTradeAppPayRequest;

use think\Db;


class Order
{
	/**
	 * 获取列表
	 * @Author   CCH
	 * @DateTime 2020-05-23T12:18:51+0800
	 * @return   结果列表
	 */
    public function getList($map=[]){
        $model = new OrderModel();
        $map[] = ['is_delete','=',0];
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->field('uuid,order_sn,places_uuid,name,phone,order_time,pay_type,status,order_time')
            ->json(['order_time'])
            ->where($map)
            ->order('create_time','desc')
            ->paginate($page_param)
            ->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['places'] = PlacesModel::where('uuid',$vo['places_uuid'])->value('name');
            $list['data'][$k]['duration'] = count($vo['order_time']);
            $list['data'][$k]['refund_time'] = OrderRefundModel::where('order_uuid',$vo['uuid'])->value('refund_time');
            $list['data'][$k]['refund_uuid'] = OrderRefundModel::where('order_uuid',$vo['uuid'])->value('uuid');
        }
        return $list;
    }

    /**
     * 导出Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:05:51+0800
     * @return   excel下载地址
     */
    public function exportExcel($map=[]){
        request()->page_size = 99999999999;
        $list = $this->getList($map);
        $list = $list['data'];

        $data = [];
        $data[] = ['订单编号', '下单时间', '预约场地','联系人','联系电话','预定时间','支付方式','状态'];
        foreach ($list as $k => $vo) {
            $text = '';
            switch ($vo['status']){
                case 1:
                    $text = '待支付/待审核';
                    break;
                case 2:
                    $text = '待开始';
                    break;
                case 3:
                    $text = '进行中';
                    break;
                case 4:
                    $text = '已完成';
                    break;
                case 5:
                    $text = '已取消';
                    break;
            }
            $tmp = [
                ' '.$vo['order_sn'].' ',
                $vo['create_time'],
                $vo['places'],
                $vo['name'],
                $vo['phone'],
                $vo['from'].'-'.$vo['to'],
                $vo['pay_type'] == 1?'立即支付':'月结支付',
                $text
            ];

            foreach ($tmp as $tmp_k => $tmp_v) {
                $tmp[$tmp_k] = $tmp_v.'';
            }
            $data[] = $tmp;
        }

        try{
            $excel = new \PHPExcel();
            $excel_sheet = $excel->getActiveSheet();
            $excel_sheet->fromArray($data);
            $excel_writer = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
            
            $file_name = '订单数据.xlsx';
            $file_path = './excel/'.$file_name;
            $excel_writer->save($file_path);
            if (!file_exists($file_path)) {
                throw new \Exception("Excel生成失败");
            }
            $result = uploadFile($file_name,$file_path,'xmh_motion/');
            return $result;
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @return         [description]
     */
    public function read($uuid){
        $model = new OrderModel();
        $res = $model->field('o.*,p.name as places_name,p.address,p.num as places_num')
            ->alias('o')
            ->leftJoin('places p','p.uuid = o.places_uuid')
            ->where('o.uuid',$uuid)
            ->json(['services','order_time'])
            ->find();
        $res->change = OrderChangeModel::field('o_order_time,n_order_time')->json(['o_order_time','n_order_time'])->where('order_uuid',$uuid)->find();
        $is_change = 0;
        $time = ceil((strtotime($res->order_time[0]->from) - time())/3600);
        if(!$res->change && $time > 4){
            $is_change = 1;
        }
        $res->is_change = $is_change;
        $res->renew = OrderRenewModel::field('renew_time,create_time')->where(['order_uuid'=>$uuid,'user_uuid'=>input('user_uuid')])->find();
        $res->renew_price = $res->price/(count($res->order_time)*2);
        return $res;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $places = PlacesModel::json(['disabled'])->where('uuid',$save_data['places_uuid'])->find();
        if(!$places){
            throw new \Exception("场地不存在");
        }
        //容纳人数
        if($places->num < input('num')){
            throw new \Exception("参会人员已经达该会议室可容纳上限，请重新输入或选择其他会议室");
        }
        $order_time = json_decode(input('order_time'),true);

        //判断时间
        foreach($places->disabled as $v){
            foreach($order_time as $val){
                if($val['from'] == $v->from){
                    throw new \Exception("场地时间已锁定，请选择其他时间段");
                }
            }
        }
        $reserved = OrderModel::field('order_time')->order('create_time desc')->json(['order_time'])->where([['places_uuid','=',$save_data['places_uuid']],['status','in',[1,2,3]]])->select();
        foreach($reserved as $v){
            foreach ($v->order_time as $vol){
                foreach($order_time as $key=>$val){
                    if($val['from'] == $vol->from){
                        throw new \Exception("场地已有预约，请选择其他时间段");
                    }
                    //已预约的时间
                    $hour = date('H',strtotime($val['from']));
                    if($hour == 14 && date('Y-m-d H:i',strtotime($vol->to)+7200) == $val['from']){
                        //throw new \Exception("连续两场会议中间需要间隔1个时间段做为清洁时间");
                    }
                    if(date('Y-m-d H:i',strtotime($vol->to)+3600) == $val['to']){
                        throw new \Exception("连续两场会议中间需要间隔1个时间段做为清洁时间");
                    }
                    if($hour == 9 && date('Y-m-d H:i',strtotime($vol->to)+3600*15) == $val['from']){
                        //throw new \Exception("连续两场会议中间需要间隔1个时间段做为清洁时间");
                    }
                }
            }
        }
        //判断当前时间
        foreach($order_time as $val){
            $time = date('Y-m-d H:i',time()+3600);
            //if($time > date('Y-m-d H:i:s',strtotime($val['from']))){
                //throw new \Exception("已过时的时间段不能预约");
            //}
            if($time > $val['from']){
                throw new \Exception("已过时的时间段不能预约");
            }

        }

        //判断月结编号
        if(input('pay_type') == 2){
            if(!PayNumbersModel::where('description',input('pay_number'))->count()){
                throw new \Exception("月结编号有误");
            }
            $save_data['pay_number'] = input('pay_number');
            $save_data['status'] = 1;
            //更新用户公司
            UserModel::where('uuid',input('user_uuid'))->Update(['company'=>input('company')]);
            //下单提醒
            $app = app('wechat.mini_program');
            $data = [
                'template_id' => 'GYYo-oa6vLeSLTpuHVYAu0qLPsSLa1CMn9cqhHQ-IAA', // 所需下发的订阅模板id
                'touser' => UserModel::where('uuid',input('user_uuid'))->value('openid'),     // 接收者（用户）的 openid
                //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
                'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                    'thing11' => [
                        'value' => $places->name, //订单信息
                    ],
                    'amount3' => [
                        'value' => '￥'.round($places->price*count($order_time),2), //订单金额
                    ],
                    'time5' => [
                        'value' => date('Y-m-d H:i:s'), //订单时间
                    ],
                    'thing4' => [
                        'value' => '下单成功,请等待审核', //订单备注
                    ]
                ],
            ];
            $res = $app->subscribe_message->send($data);
            //后台通知
            NotifyModel::create([
                'uuid'=>uuidCreate(),
                'content'=> '用户'.UserModel::where('uuid',input('user_uuid'))->value('nickname').'支付订单需要审核'
            ]);

        }else{
            $save_data['status'] = 0;
        }
        $model = new OrderModel();
        $uuid = uuidCreate();
        $save_data['uuid'] = $uuid;
        $save_data['order_sn'] = numberCreate();
        $save_data['order_time'] = input('order_time');
        $save_data['price'] = $places->price*count($order_time);

        $curtime = date('Y-m-d H:i:s');
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['update_time'] = $curtime;
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
            }
            if(input('pay_type') == 2){
                //系统流水
                SystemBillModel::insert(['uuid'=>uuidCreate(),'user_uuid'=>input('user_uuid'),'amount'=>$places->price*count($order_time),'type'=>0,'bill_sn'=>numberCreate(),'order_uuid'=>$uuid,'create_time'=>date('Y-m-d H:i:s',time())]);
            }
            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1,'uuid'=>$save_data['uuid']];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    


    /**
     * 更新
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function update($save_data){
        $order_time = json_decode(input('order_time'),true);
        $times = OrderModel::where('uuid',$save_data['uuid'])->value('order_time');
        //判断改签总时间是否一致
        if(count($order_time) != count(json_decode($times))){
            throw new \Exception("改签的总时间要和原来的一致");
        }

        //开始前4小时不能改签
        $time = ceil((strtotime(json_decode($times)[0]->from) - time())/3600);

        if($time < 5){
            throw new \Exception("开始前4小时不能改签");
        }

        $places = PlacesModel::json(['disabled'])->where('uuid',OrderModel::where('uuid',$save_data['uuid'])->value('places_uuid'))->find();
        if(!$places){
            throw new \Exception("场地不存在");
        }

        //判断时间
        foreach($places->disabled as $v){
            foreach($order_time as $val){
                if($val['from'] == $v->from){
                    throw new \Exception("场地时间已锁定，请选择其他时间段");
                }
            }
        }
        $reserved = OrderModel::field('order_time')->json(['order_time'])->where([['places_uuid','=',$save_data['places_uuid']],['status','in',[1,2]]])->select();
        foreach($reserved as $v){
            foreach ($v->order_time as $vol){
                foreach($order_time as $val){
                    if($val['from'] == $vol->from){
                        throw new \Exception("场地已有预约，请选择其他时间段");
                    }
                }
            }
        }

        //判断当前时间
        foreach($order_time as $val){
            if(date('Y-m-d H:i:s',time()) > $val['from']){
                throw new \Exception("已过时的时间段不能预约");
            }
        }
        //判断改签记录
        if(OrderChangeModel::where('order_uuid',$save_data['uuid'])->count()){
            throw new \Exception("每个订单只能在有效期内改签一次");
        }


        $model = new OrderModel();
        // 启动事务 
        Db::startTrans();
        try{
            if (!empty($save_data)) {

                //添加改签记录
                $order = OrderModel::where('uuid',$save_data['uuid'])->find();
                $order_change = new OrderChangeModel();
                $order_change->save([
                    'uuid'=>uuidCreate(),
                    'o_order_time'=>$order->order_time,
                    'n_order_time'=>$save_data['order_time'],
                    'order_uuid'=>$save_data['uuid']
                ]);
                if ( $model->where('uuid',$save_data['uuid'])->update($save_data) === false ) {
                    throw new \Exception("保存失败");
                }
            }
            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    /**
     * 取消订单
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function cancel($uuid){
        $order = OrderModel::field('status,pay_type')->where(['uuid'=>$uuid,'user_uuid'=>input('user_uuid')])->find();
        if($order->status != 1 && $order->pay_type == 1){
            return ['status'=>0,'msg'=>'该订单无法取消'];
        }
        $model = new OrderModel();
        $data = $model->where('uuid',$uuid)->update(['status'=>5,'update_time'=>date('Y-m-d H:i:s',time()),'cancel_time'=>date('Y-m-d H:i:s',time())]);
        if($data){
            //后台通知
            NotifyModel::create([
                'uuid'=>uuidCreate(),
                'content'=> '用户'.UserModel::where('uuid',input('user_uuid'))->value('nickname').'取消了订单'
            ]);
            return ['status'=>1];
        }else{
            return ['status'=>0];
        }
    }

    /**
     * 删除订单
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function delete($uuid){
        $model = new OrderModel();
        $data = $model->where('uuid',$uuid)->find();
        // 启动事务 
        Db::startTrans();
        try{
            if (!in_array($data['status'], [5])) {
                throw new \Exception("订单当前状态无法删除");
            }
            
            $save_data = [];
            $save_data['is_delete'] = 1;
            $save_data['update_time'] = date('Y-m-d H:i:s');
            if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
                throw new \Exception("保存失败");
            }
            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }






    /**
     * 支付订单
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function pay($uuid,$save_data=[]){
        $model = new OrderModel();
        $data = $model->where('uuid',$uuid)->find();
        // 启动事务 
        Db::startTrans();
        try{
            if ($data['status'] != 0 || $save_data['is_refund'] != 0) {
                throw new \Exception("订单当前状态无法操作");
            }
            $save_data['pay_time'] = $save_data['update_time'] = date('Y-m-d H:i:s');
            $save_data['status'] = 1;
            if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
                throw new \Exception("保存失败");
            }

            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    public function renew($save_data){
        $save_data['renew_time'] = $save_data['renew_time']*0.5;
        if(OrderRenewModel::where(['user_uuid'=>input('user_uuid'),'order_uuid'=>$save_data['order_uuid'],'status'=>1])->count()){
            throw new \Exception("续钟只能办理一次");
        }
        if($save_data['pay_type'] == 2){
            if(!PayNumbersModel::where('description',$save_data['pay_number'])->count()){
                throw new \Exception("月结编号有误");
            }
            $save_data['status'] = 1;
        }
        $price = PlacesModel::where('uuid',OrderModel::where(['uuid'=>$save_data['order_uuid'],'user_uuid'=>input('user_uuid')])->value('places_uuid'))->value('price');
        if(!$price){
            throw new \Exception("订单有误");
        }
        $save_data['price'] = $price*$save_data['renew_time'];
        $save_data['order_sn'] = numberCreate();
        $save_data['uuid'] = uuidCreate();
        $save_data['user_uuid'] =  input('user_uuid');
        // 启动事务
        Db::startTrans();
        try{
            $model = new OrderRenewModel();
            if ( $model->save($save_data) === false ) {
                throw new \Exception("保存失败");
            }
            //系统流水
            if($save_data['pay_type'] == 2){
                //系统流水
                SystemBillModel::insert(['uuid'=>uuidCreate(),'user_uuid'=>input('user_uuid'),'amount'=>$price*$save_data['renew_time'],'type'=>2,'bill_sn'=>numberCreate(),'order_uuid'=>$save_data['order_uuid'],'create_time'=>date('Y-m-d H:i:s',time())]);
            }
            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1,'uuid'=>$save_data['uuid']];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }
}
