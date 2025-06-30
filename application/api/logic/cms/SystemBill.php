<?php
namespace app\api\logic\cms;
use app\api\model\SystemBill as SystemBillModel;
use app\api\model\User as UserModel;
use app\api\model\Order as OrderModel;
use app\api\model\Institution as InstitutionModel;
use app\api\model\Course as CourseModel;
use think\Db;


class SystemBill
{
    /**
     * 头部统计
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   头部统计
     */
    public function statistics($map=[]){
        $model = new SystemBillModel();
        $data = [
            'type_0'=>$model->where('type',0)->sum('amount'),
            'type_1'=>abs($model->where('type',1)->sum('amount'))
        ];
        return $data;
    }

	/**
	 * 获取列表
	 * @Author   CCH
	 * @DateTime 2020-05-23T12:18:51+0800
	 * @return   结果列表
	 */
    public function getList($map=[]){
        $model = new SystemBillModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->alias('s')
            ->field('s.*,o.order_sn,u.nickname,u.mobile,o.pay_number,o.pay_type')
            ->leftJoin('user u','u.uuid = s.user_uuid')
            ->leftJoin('order o','o.uuid = s.order_uuid')
            ->where($map)
            ->order('s.create_time','desc')
            ->paginate($page_param)
            ->toarray();
        return $list;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new SystemBillModel();
        // 启动事务 
        try{
            $save_data['uuid'] = uuidCreate();
            $save_data['bill_sn'] = numberCreate();
            if (!isset($save_data['type'])) {
                $save_data['type'] = $save_data['amount'] > 0 ? 0 : 1; 
            }
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
            }

            // if (StoreModel::where('uuid',$save_data['store_uuid'])->inc('commission',$save_data['amount'])->update() === false) {
            //     throw new \Exception("同步失败");
            // }
            // 更新成功 提交事务
            return ['status'=>1,'uuid'=>$save_data['uuid']];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
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
        $model = new SystemBillModel();
        $data = $model->where('uuid',$uuid)->find();
        // 启动事务 
        Db::startTrans();
        try{
            if (!empty($save_data)) {
                if ( $data['status'] == 0 && $save_data['status'] == 1) {
                    $save_data['is_read'] = 0;
                }
                if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
                    throw new \Exception("保存失败");
                }
                // 发送短信通知
                if ( $data['status'] == 0 && $save_data['status'] == 1) {
                    $mobile = Db::name('user')->where('uuid',$data['user_uuid'])->value('mobile');
                    $sms_status = sendSms($mobile,[],'SMS_205461901');
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
        $model = new SystemBillModel();
        $data = $model->alias('s')
            ->field('s.*,o.order_sn,u.nickname,u.mobile')
            ->leftJoin('user u','u.uuid = s.user_uuid')
            ->leftJoin('order o','o.uuid = s.order_uuid')
            ->where('s.uuid',$uuid)
            ->find();
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
        $model = new SystemBillModel();
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
        $data[] = ['交易编号', '月结编号','用户昵称', '手机','订单号','金额','交易类型','付款类型','交易时间'];

        foreach ($list as $k => $vo) {
            $tmp = [
                ' '.$vo['bill_sn'].' ',
                ' '.$vo['pay_number'].' ',
                $vo['nickname'],
                ' '.$vo['mobile'].' ',
                ' '.$vo['order_sn'].' ',
                $vo['amount'],
                $vo['pay_type'] == 1?'立即支付':'月结支付',
                $vo['type'] == 1?'订单退订':'订单预定',
                $vo['create_time']
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
            
            $file_name = '平台流水数据.xlsx';
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
}
