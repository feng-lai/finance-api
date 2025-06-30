<?php
namespace app\api\logic\mini;
use app\api\model\OrderRefund as OrderRefundModel;
use app\api\model\SystemBill as SystemBillModel;
use app\api\model\Order as OrderModel;
use app\api\model\User as UserModel;
use app\api\model\Institution as InstitutionModel;
use app\api\model\OrderTeacher as OrderTeacherModel;
use app\api\model\Teacher as TeacherModel;
use app\api\model\Course as CourseModel;
use app\api\model\UserCoupon as UserCouponModel;
use app\api\model\PublicAudit as PublicAuditModel;
use think\Db;


class OrderRefund
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new OrderRefundModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time desc')->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $order = OrderModel::field('uuid,order_sn,pay_type,total_price,expect_time,course_data')->where('uuid',$vo['order_uuid'])->find();
            $course = json_decode($order['course_data'],true);
            unset($order['course_data']);
            if ($vo['user_type'] == 0) {
                $list['data'][$k]['user'] = UserModel::field('uuid,nickname,mobile,truename,headimgurl')->where('uuid',$vo['user_uuid'])->find();
            }else{
                $list['data'][$k]['institution'] = InstitutionModel::field('uuid,name,mobile')->where('uuid',$vo['user_uuid'])->find();
            }
            $list['data'][$k]['course'] = $course;
            $list['data'][$k]['order'] = $order;

            $teacher_uuids = OrderTeacherModel::where('order_uuid',$order['uuid'])->column('teacher_uuid');
            $list['data'][$k]['teacher'] = TeacherModel::field('uuid,truename,headimgurl,mobile')->where('uuid','in',$teacher_uuids)->select();
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
        $data[] = ['订单号', '用户昵称', '用户手机号','所选课程','课程类型','退费原因','退款金额','提交时间','退费时间','状态'];
        foreach ($list as $k => $vo) {
            $tmp = [
                $vo['order']['order_sn'],
                $vo['user_type']==0?$vo['user']['nickname']:$vo['institution']['name'],
                $vo['user_type']==0?$vo['user']['mobile']:$vo['institution']['mobile'],
                $vo['course']['name'],
                CourseModel::getTypeCn($vo['course']['type']),
                $vo['reason'],$vo['fee'],$vo['create_time'],$vo['update_time'],
                OrderRefundModel::getStatusCn($vo['status'])
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

            $file_name = '退费数据.xlsx';
            $file_path = './excel/'.$file_name;
            $excel_writer->save($file_path);
            if (!file_exists($file_path)) {
                throw new \Exception("Excel生成失败");
            }
            $result = uploadFileExcel($file_name,$file_path,'hld_education/excel/');
            return $result;
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
        $model = new OrderRefundModel();
        $data = $model->where('uuid',$uuid)->find();
        // 启动事务
        Db::startTrans();
        try{
            if (!empty($save_data)) {
                if ($data['status'] != 0) {
                    unset($save_data['status']);
                }
                $save_data['update_time'] = date('Y-m-d H:i:s');
                if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
                    throw new \Exception("保存失败");
                }
                if ($data['status'] == 0) {
                    if ($save_data['status'] == -1) {
                        OrderModel::where('uuid',$data['order_uuid'])->update([
                            'is_refund'=>0
                        ]);
                    }elseif ($save_data['status'] == 1) {
                        OrderModel::where('uuid',$data['order_uuid'])->update([
                            'is_refund'=>0,
                            'status'=>-1
                        ]);

                        // 返还优惠券
                        $order = OrderModel::where('uuid',$data['order_uuid'])->find();
                        if (!empty($order['user_coupon_uuid'])) {
                            UserCouponModel::where('uuid',$order['user_coupon_uuid'])->update([
                                'order_uuid'=>null,
                                'finish_time'=>null,
                                'status'=>0
                            ]);
                        }

                        // 对公取消
                        PublicAuditModel::where([
                            ['order_uuid','=',$order['uuid']],
                            ['status','=',0],
                        ])->update(['status'=>1]);

                        // 同步将订单相关排课设为删除
                        Db::name('order_lesson')->where('order_uuid',$data['order_uuid'])->update(['is_delete'=>1]);

                        // 添加系统流水
                        (new \app\api\logic\cms\SystemBill())->saveData([
                            'user_uuid'=>$data['user_uuid'],
                            'user_type'=>$data['user_type'],
                            'course_type'=>$data['course_type'],
                            'order_uuid'=>$data['order_uuid'],
                            'amount'=>-$data['fee'],
                            'remark'=>'订单退款',
                            'bill_type'=>2
                        ]);

                        // 极光推送
                        $course = json_decode($order['course_data'],true);
                        (new \app\common\tools\JPush())->push('海海直播','课程:'.$course['name'].'已取消,详情请点击查看',$order['user_uuid'],['type'=>0,'order_uuid'=>$order['uuid']]);
                    }
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
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @return         [description]
     */
    public function read($uuid){
        $model = new OrderRefundModel();
        $data = $model->field('r.fee,r.refund_time,r.status')
            ->alias('r')
            ->leftJoin('order o','o.uuid = r.order_uuid')
            ->where('r.uuid',$uuid)
            ->find();
        /**微信退款
        $app = app('wechat.payment');
        // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $app->refund->queryByOutRefundNumber($data->refund_sn);
        print_r($result);exit;

        if($result['return_code'] == 'FAIL'){
            throw new \Exception($result['return_msg']);
        }

        if($result['result_code'] == 'FAIL'){
            throw new \Exception($result['err_code_des']);
        }

        if($result['refund_status_0'] <> 'SUCCESS'){
            throw new \Exception('失败');
            $data->status = 1;
        }else{
            $data->status = -1;
        }
         * **/
        return $data;
    }

    /**
     * 删除
     * @Author   cch
     * @DateTime 2020-06-10T15:19:51+0800
     * @param    [type]                   $uuid [description]
     * @return   [type]                         [description]
     */
    public function delete($uuid){
        $model = new OrderRefundModel();
        Db::startTrans();
        try{
            if ( !$model->where('uuid',$uuid)->delete() ) {
                throw new \Exception("删除失败");
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

    public function save(){
        $info = OrderModel::json(['order_time'])->where(['uuid'=>input('uuid'),'user_uuid'=>input('user_uuid')])->find();
        if(!$info){
            throw new \Exception("无订单数据");
        }
        //扣除金额
        $price = $info->price;

        //是否到了开始时间
        if(strtotime($info->order_time[0]->from) >= (time()+72*60*60)){
            $price = 0;
        }
        if(strtotime($info->order_time[0]->from) < (time()+72*60*60) && strtotime($info->order_time[0]->from) >= (time()+24*60*60) ){
            $price = $info->price*0.2;
        }
        if(strtotime($info->order_time[0]->from) < (time()+24*60*60) && strtotime($info->order_time[0]->from) >= (time()+4*60*60) ){
            $price = $info->price*0.5;
        }
        Db::startTrans();
        try{
            $model = new OrderRefundModel();
            $uuid = uuidCreate();
            $refund_sn = numberCreate();
            $fee = $info->price - $price;

            if($info->pay_type == 1 && $price != $info->price){
                //微信退款
                $app = app('wechat.payment');
                // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
                $result = $app->refund->byOutTradeNumber($info->order_sn, $refund_sn, $info->price*100, $fee*100, [
                    // 可在此处传入其他参数，详细参数见微信支付文档
                    'refund_desc' => '退订',
                    'notify_url'=>'https://jrpt-api.vanke.com/v1/mini/OrderRefund_reback'
                ]);

                if($result['return_code'] == 'FAIL'){
                    throw new \Exception($result['return_msg']);
                }

                if($result['result_code'] == 'FAIL'){
                    throw new \Exception($result['err_code_des']);
                }

            }

            //if($info->pay_type == 2 || $price == $info->price){
                //订单改为取消5
                OrderModel::where(['uuid'=>input('uuid'),'user_uuid'=>input('user_uuid')])->update(['status'=>5,'update_time'=>date('Y-m-d H:i:s',time())]);
            //}

            if($info->pay_type == 2){
                //系统流水
                SystemBillModel::insert(['uuid'=>uuidCreate(),'user_uuid'=>input('user_uuid'),'amount'=>$info->price,'type'=>1,'bill_sn'=>numberCreate(),'order_uuid'=>$info->uuid,'create_time'=>date('Y-m-d H:i:s',time())]);
            }

            //先删除旧的记录
            OrderRefundModel::where(['order_uuid'=>input('uuid'),'user_uuid'=>input('user_uuid')])->delete();
            $res = $model->save([
                'uuid'=>$uuid,
                'refund_sn'=>$refund_sn,
                'refund_time'=>($info->pay_type == 2 || $price == $info->price) ? date('Y-m-d H:i:s',time()):null,
                'status'=>($info->pay_type == 2 || $price == $info->price) ?2:1,
                    'order_uuid'=>input('uuid'),
                'user_uuid'=>input('user_uuid'),
                'fee'=>$fee,
                'c_fee'=>$price,
            ]);

            if (!$res) {
                throw new \Exception("失败");
            }

            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1,'data'=>$uuid];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    public function price(){
        $info = OrderModel::json(['order_time'])->where(['uuid'=>input('uuid'),'user_uuid'=>input('user_uuid')])->find();
        if(!$info){
            throw new \Exception("无订单数据");
        }
        //扣除金额
        $price = $info->price;

        //是否到了开始时间
        if(strtotime($info->order_time[0]->from) >= (time()+72*60*60)){
            $price = 0;
        }
        if(strtotime($info->order_time[0]->from) < (time()+72*60*60) && strtotime($info->order_time[0]->from) >= (time()+24*60*60) ){
            $price = $info->price*0.2;
        }
        if(strtotime($info->order_time[0]->from) < (time()+24*60*60) && strtotime($info->order_time[0]->from) >= (time()+4*60*60) ){
            $price = $info->price*0.5;
        }

        //$fee = $info->price - $price;

        return ['status'=>1,'data'=>$price];
    }


}
