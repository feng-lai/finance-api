<?php
namespace app\api\logic\cms;
use app\api\model\OrderRefund as OrderRefundModel;
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
            $list['data'][$k]['user'] = UserModel::field('uuid,nickname,mobile,headimgurl')->where('uuid',$vo['user_uuid'])->find();
            $list['data'][$k]['order'] = OrderModel::field('uuid,order_sn,pay_type,price')->where('uuid',$vo['order_uuid'])->find();
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
    public function updateData($uuid,$status){
        $model = new OrderRefundModel();
        $data = $model->where('uuid',$uuid)->find();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['status'] = $status;
            $save_data['update_time'] = date('Y-m-d H:i:s');

            if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
                throw new \Exception("保存失败");
            }
            if ($save_data['status'] == 1) {

                $order = OrderModel::where('uuid',$data->order_uuid)->find();

                $app = app('wechat.payment');

                // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
                $result = $app->refund->byOutTradeNumber($order->order_sn, $data->refund_sn, $order->price*100, $data->fee*100, [
                    // 可在此处传入其他参数，详细参数见微信支付文档
                    'refund_desc' => '退费',
                ]);

                if($result['return_code'] == 'FAIL'){
                    throw new \Exception($result['return_msg']);
                    OrderRefundModel::where('uuid',$uuid)->update(['status'=>0]);
                }

                if($result['result_code'] == 'FAIL'){
                    throw new \Exception($result['err_code_des']);
                    OrderRefundModel::where('uuid',$uuid)->update(['status'=>0]);
                }

                OrderRefundModel::where('uuid',$uuid)->update(['refund_time'=>date('Y-m-d H:i:s')]);

                //订单状态改为取消
                OrderModel::where('uuid',$data->order_uuid)->update(['status'=>5,'refund_time'=>date('Y-m-d H:i:s',time()),'update_time'=>date('Y-m-d H:i:s',time())]);

                // 添加系统流水
                (new \app\api\logic\cms\SystemBill())->saveData([
                    'user_uuid'=>$data['user_uuid'],
                    'order_uuid'=>$data['order_uuid'],
                    'amount'=>$data['fee'],
                    'type'=>1,
                    'bill_sn'=>numberCreate()
                ]);
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
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($data)) {
            $order = OrderModel::field('uuid,order_sn,pay_type,total_price,expect_time,course_data')->where('uuid',$data['order_uuid'])->find();
            $course = json_decode($order['course_data'],true);
            unset($order['course_data']);
            if ($data['user_type'] == 0) {
                $data['user'] = UserModel::field('uuid,nickname,mobile,truename,headimgurl')->where('uuid',$data['user_uuid'])->find();
            }else{
                $data['institution'] = InstitutionModel::field('uuid,name,mobile')->where('uuid',$data['user_uuid'])->find();
            }
            $data['course'] = $course;
            $data['order'] = $order;

            $teacher_uuids = OrderTeacherModel::where('order_uuid',$order['uuid'])->column('teacher_uuid');
            $data['teacher'] = TeacherModel::field('uuid,truename,headimgurl,mobile')->where('uuid','in',$teacher_uuids)->select();
        }
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
}
