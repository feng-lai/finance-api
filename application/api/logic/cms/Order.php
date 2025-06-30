<?php
namespace app\api\logic\cms;
use app\api\model\Order as OrderModel;
use app\api\model\User as UserModel;
use app\api\model\OrderRefund as OrderRefundModel;
use app\api\model\Config as ConfigModel;
use app\api\model\Comment as CommentModel;
use app\api\model\Places as PlacesModel;
use app\api\model\SystemBill as SystemBillModel;

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
        $list = $model->field('uuid,order_sn,places_uuid,name,phone,order_time,pay_type,status,company,create_time')->json(['order_time'])->where($map)->order('create_time','desc')->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['places'] = PlacesModel::where('uuid',$vo['places_uuid'])->value('name');
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
            $str = [];
            foreach($vo['order_time'] as $v){
                $str[] = date('Y-m-d H:i',strtotime($v->from)).'-'.date('H:i',strtotime($v->to));

            }
            $str = implode(',',$str);
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
                ' '.$vo['phone'].' ',
                $str,
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
        $res = $model->field('o.*,p.name as places_name,u.nickname,u.mobile,r.fee,r.c_fee,r.refund_time')
            ->alias('o')
            ->leftJoin('places p','p.uuid = o.places_uuid')
            ->leftJoin('user u','u.uuid = o.user_uuid')
            ->leftJoin('order_refund r','r.order_uuid = o.uuid')
            ->where('o.uuid',$uuid)
            ->json(['services','order_time'])
            ->find();
        if($res['c_fee']){
            $res['c_fee'] = round($res['c_fee']/$res['price'],2);
        }
        return $res;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new OrderModel();
        $save_data['uuid'] = uuidCreate();
        $save_data['order_sn'] = numberCreate();
        $save_data['user_type'] = UserModel::where('uuid',$save_data['user_uuid'])->count() > 0 ? 0 : 1;

        $course = CourseModel::where('uuid',$save_data['course_uuid'])->find();
        if ($course['stock'] < 1) {
            return ['status'=>0,'msg'=>'库存不足'];
        }
        $save_data['course_price'] = $course['price'];
        // 如果不在优惠时间内，则使用原价
        if ( !(!empty($course['discount_end']) && strtotime($course['discount_start']) < time() && strtotime($course['discount_end']) > time()) ) {
            $save_data['course_price'] = $course['old_price'];
        }

        $save_data['discount_price'] = 0;
        // 使用优惠券
        if (!empty($save_data['user_coupon_uuid'])) {
            $user_coupon = UserCouponModel::where('uuid',$save_data['user_coupon_uuid'])->find();
            if ($user_coupon['status'] != 0) {
                return ['status'=>0,'msg'=>'优惠券无法使用：状态异常'];
            }
            if ( !(strtotime($user_coupon['start_time']) < time() && strtotime($user_coupon['end_time']) > time()) ) {
                return ['status'=>0,'msg'=>'优惠券无法使用：未符合使用时间'];
            }
            if ( !in_array($course['uuid'], explode(',', $user_coupon['course_uuid'])) ) {
                return ['status'=>0,'msg'=>'优惠券无法使用：不能用于该课程'];
            }
            if( $user_coupon['type'] == 0 && $save_data['course_price'] < $user_coupon['condition'] ) {
                return ['status'=>0,'msg'=>'优惠券无法使用：满减金额不足'];
            }
            // if($user_coupon['type'] == 1){
            //     $save_data['discount_price'] = $user_coupon['discount'];
            // }else{
            //     $save_data['discount_price'] = $save_data['appraisal_price'] * (1 - $user_coupon['discount'] / 10);
            // }

            $save_data['discount_price'] = $user_coupon['discount'];
            // 如果优惠金额超过鉴定金额，则 = 鉴定金额
            if ($save_data['discount_price'] > $save_data['course_price']) {
                $save_data['discount_price'] = $save_data['course_price'];
            }
        }

        // $save_data['total_price'] = $save_data['course_price'] - $save_data['discount_price'];
        if ($save_data['total_price'] <= 0) {
            // return ['status'=>0,'msg'=>'订单金额需大于0'];
            $save_data['total_price'] = 0;
            $save_data['status'] = 1;
        }

        $save_data['course_data'] = [
            'name'=>$course['name'],
            'name_en'=>$course['name_en'],
            'cover'=>$course['cover'],
            'main_tags'=>$course['main_tags'],
            'fit_tags'=>$course['fit_tags'],
            'price'=>$course['price'],
            'old_price'=>$course['old_price'],
            'type'=>$course['type'],
            'lesson_num'=>$course['lesson_num'],
            'params'=>json_decode($course['params'],true)
        ];
        $save_data['course_type'] = $course['type'];
        $save_data['course_data'] = json_encode($save_data['course_data'],JSON_UNESCAPED_UNICODE);

        $save_data['is_admin'] = 1;

        $curtime = date('Y-m-d H:i:s');
        // 启动事务
        Db::startTrans();
        try{
            $save_data['update_time'] = $curtime;
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
            }

            if (!empty($save_data['user_coupon_uuid'])) {
                UserCouponModel::where('uuid',$save_data['user_coupon_uuid'])->update([
                    'order_uuid'=>$save_data['uuid'],
                    'finish_time'=>$curtime,
                    'status'=>1
                ]);
            }

            $ol_datas = [];
            $lessons = CourseLessonModel::where('course_uuid',$course['uuid'])->select();
            foreach ($lessons as $k => $lesson) {
                $ol = [
                    'uuid'=>uuidCreate(),
                    'user_uuid'=>$save_data['user_uuid'],
                    'course_type'=>$save_data['course_type'],
                    'user_type'=>$save_data['user_type'],
                    'order_uuid'=>$save_data['uuid'],
                    'course_uuid'=>$course['uuid'],
                    'name'=>$lesson['name'],
                    'lesson'=>$lesson['lesson'],
                    'create_time'=>$curtime
                ];
                if ($course['type'] == 3) {
                    $ol['is_init'] = 1;
                    $ol['start_time'] = $lesson['start_time'];
                    $ol['end_time'] = $lesson['end_time'];
                    $ol['teacher_uuid'] = $lesson['teacher_uuid'];
                }
                $ol_datas[] = $ol;
            }
            if ( !empty($ol_datas) && !OrderLessonModel::insertAll($ol_datas) ) {
                throw new \Exception("保存课节失败");
            }

            $teacher_uuids = CourseTeacherModel::where('course_uuid',$course['uuid'])->column('teacher_uuid');
            $ot_datas = [];
            foreach ($teacher_uuids as $k => $teacher_uuid) {
                $ot_datas[] = [
                    'order_uuid'=>$save_data['uuid'],
                    'teacher_uuid'=>$teacher_uuid
                ];
            }
            if ( !empty($ot_datas) && !OrderTeacherModel::insertAll($ot_datas) ) {
                throw new \Exception("保存教师失败");
            }

            // 变更用户的阶段
            $uinfo = UserModel::where('uuid',$save_data['user_uuid'])->find();
            if ($uinfo['stage'] == 0 || $uinfo['stage'] == 1) {
                $stage = $uinfo['stage'] == 0 ? 1 : 2;
                UserModel::where('uuid',$save_data['user_uuid'])->update(['stage'=>$stage]);
            }

            // 更新成功 提交事务
            Db::commit();

            // 统计小班课是否符合成班
            if ($save_data['course_type'] == 3 && $save_data['status'] > 0) {
                $model->statisticsCourse_3($save_data['course_uuid']);
            }
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
    public function updateData($uuid,$save_data){
        $model = new OrderModel();
        // 启动事务
        Db::startTrans();
        try{
            if (!empty($save_data)) {
                if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
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
        $model = new OrderModel();
        $data = $model->where('uuid',$uuid)->update(['status'=>5,'update_time'=>date('Y-m-d H:i:s')]);
        if($data){
            $info = OrderModel::where('uuid',$uuid)->find();
            if($info->pay_type == 1){
                //退费
                $refund_sn = numberCreate();
                //微信退款
                $app = app('wechat.payment');
                // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
                $result = $app->refund->byOutTradeNumber($info->order_sn, $refund_sn, $info->price*100, $info->price*100, [
                    // 可在此处传入其他参数，详细参数见微信支付文档
                    'refund_desc' => '审核不通过退款',
                    'notify_url'=>'https://jrpt-api.vanke.com/v1/mini/OrderRefund_reback'
                ]);

                if($result['return_code'] == 'FAIL'){
                    throw new \Exception($result['return_msg']);
                }

                if($result['result_code'] == 'FAIL'){
                    throw new \Exception($result['err_code_des']);
                }

                //退订申请
                OrderRefundModel::insert(['uuid'=>uuidCreate(),'user_uuid'=>$info->user_uuid,'fee'=>$info->price,'order_uuid'=>$info->uuid,'create_time'=>date('Y-m-d H:i:s',time()),'refund_sn'=>$refund_sn]);
            }
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
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function invoiceApply($save_data){
        $model = new OrderInvoiceModel();
        $data = OrderModel::where('uuid',$save_data['order_uuid'])->find();
        // 启动事务
        Db::startTrans();
        try{
            if (empty($data) || $data['status'] != 6) {
                throw new \Exception("订单不存在或状态不符合");
            }
            if ($save_data['user_uuid'] != $data['user_uuid']) {
                throw new \Exception("申请非订单本人");
            }
            $save_data['uuid'] = uuidCreate();
            $save_data['price'] = $data['total_price'];
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
            }
            if (OrderModel::where('uuid',$save_data['order_uuid'])->update(['status'=>7]) === false) {
                throw new \Exception("更新订单状态失败");
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
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @return         [description]
     */
    public function goodsDetail($order_goods_uuid){
        $model = new OrderGoodsModel();
        $data = $model->where('uuid',$order_goods_uuid)->find();
        if (!empty($data)) {
            $order = OrderModel::where('uuid',$data['order_uuid'])->find();
            $goods_data = json_decode($data['goods_data'],true);
            $data['goods'] = $goods_data;
            unset($data['goods_data']);
            $data['avg_price'] = round($data['total_price'] / $data['num'],2);
        }
        return $data;
    }

    public function getNoReads($user_uuid){
        $data = [];
        $map = [
            ['user_uuid','=',$user_uuid],
            ['is_delete','=',0]
        ];
        $search_map = $map;
        $search_map[] = ['status','=',0];
        $data['order_status_0'] = Db::name('order')->where($search_map)->count();

        $search_map = $map;
        $search_map[] = ['status','=',2];
        $data['order_status_2'] = Db::name('order')->where($search_map)->count();

        $search_map = [
            ['order.user_uuid','=',$user_uuid],
            ['order.is_delete','=',0]
        ];
        $search_map[] = ['order.status','=',3];
        $search_map[] = Db::raw('gc.order_uuid is null');
        $data['order_status_3'] = Db::name('order')->where($search_map)
        ->join('goods_comment gc','gc.order_uuid = order.uuid','left')
        ->count();
        // $search_map = $map;
        // $search_map[] = ['status','=',3];
        // $data['order_status_3'] = Db::name('order')->where($search_map)
        // ->count();

        $search_map = [
            ['user_uuid','=',$user_uuid],
            ['status','not in',[10,11,-1]]
        ];
        $data['order_refund'] = Db::name('order_refund')->where($search_map)->count();
        return $data;
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

    public function  verify(){
        $info = OrderModel::json(['order_time'])->where('uuid',input('uuid'))->find();
        if($info->status != 1){
            throw new \Exception("订单当前状态无法操作");
        }
        $model = new OrderModel();
        if ( $model->where('uuid',input('uuid'))->update(['status'=>input('status'),'update_time'=>date('Y-m-d H:i:s')]) === false ) {
            throw new \Exception("保存失败");
        }

        //订阅消息
        $app = app('wechat.mini_program');
        $data = [
            'template_id' => 'BU69NR9nJWaESA2fyZI-xjHZN4l4OIO3MLX-rAtXQGM', // 所需下发的订阅模板id
            'touser' => UserModel::where('uuid',$info->user_uuid)->value('openid'),     // 接收者（用户）的 openid
            //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
            'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                'thing2' => [
                    'value' => PlacesModel::where('uuid',$info->places_uuid)->value('address'), //地点
                ],
                'date3' => [
                    'value' => $info->order_time[0]->from, //开始时间
                ],
                'date4' => [
                    'value' => end($info->order_time)->to, //结束时间
                ],
                'name5' => [
                    'value' => $info->name, //预约人
                ],
                'phrase1' => [
                    'value' => input('status') == 2?'通过':'不通过', //处理结果
                ]
            ],
        ];
        $res = $app->subscribe_message->send($data);


        if(input('status') == 5 && $info->pay_type == 1){

            $refund_sn = numberCreate();
            //微信退款
            $app = app('wechat.payment');
            // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
            $result = $app->refund->byOutTradeNumber($info->order_sn, $refund_sn, $info->price*100, $info->price*100, [
                // 可在此处传入其他参数，详细参数见微信支付文档
                'refund_desc' => '审核不通过退款',
                'notify_url'=>'https://jrpt-api.vanke.com/v1/mini/OrderRefund_reback'
            ]);

            if($result['return_code'] == 'FAIL'){
                throw new \Exception($result['return_msg']);
            }

            if($result['result_code'] == 'FAIL'){
                throw new \Exception($result['err_code_des']);
            }

            //退订申请
            OrderRefundModel::insert(['uuid'=>uuidCreate(),'user_uuid'=>$info->user_uuid,'fee'=>$info->price,'c_fee'=>0,'order_uuid'=>$info->uuid,'create_time'=>date('Y-m-d H:i:s',time()),'refund_sn'=>$refund_sn]);

        }
        //系统流水
        return ['status'=>1];
    }
}
