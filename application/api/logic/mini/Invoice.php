<?php
namespace app\api\logic\mini;
use app\api\model\Invoice as InvoiceModel;
use app\api\model\InvoiceTitle as InvoiceTitleModel;
use app\api\model\Order as OrderModel;
use think\Db;


class Invoice
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new InvoiceModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->field('i.uuid,i.status,i.amount,t.name as title_name,p.name,o.to')
            ->alias('i')
            ->leftJoin('order o','o.uuid = i.order_uuid')
            ->leftJoin('places p','p.uuid = o.places_uuid')
            ->leftJoin('invoice_title t','t.uuid = i.invoice_title_uuid')
            ->where($map)
            ->order('i.create_time','desc')
            ->paginate($page_param);
        return $list;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        //判断抬头
        if(!InvoiceTitleModel::where(['uuid'=>$save_data['invoice_title_uuid'],'user_uuid'=>input('user_uuid')])->count()) {
            throw new \Exception("抬头有误");
        }
        //判断订单
        $order = OrderModel::where(['uuid'=>$save_data['order_uuid'],'user_uuid'=>input('user_uuid')])->whereIn('status',[2,3,4])->value('price');
        if(!$order){
            throw new \Exception("订单有误");
        }
        //是否已开发票
        if(InvoiceModel::where(['user_uuid'=>input('user_uuid'),'order_uuid'=>$save_data['order_uuid']])->whereIn('status',[1,2])->count()){
            throw new \Exception("不能重复申请");
        }
        $model = new InvoiceModel();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['uuid'] = uuidCreate();
            $save_data['invoice_sn'] = numberCreate();
            $save_data['amount'] = $order;
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
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
    public function updateData($uuid,$save_data){
        $model = new InvoiceModel();
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
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @return         [description]
     */
    public function read($uuid){
        $model = new InvoiceModel();
        $data = $model
            ->field('i.*,u.nickname,o.order_sn,t.name as title_name,t.bank,t.bank_number,t.number,p.name,o.to')
            ->alias('i')
            ->leftJoin('user u','u.uuid = i.user_uuid')
            ->leftJoin('order o','o.uuid = i.order_uuid')
            ->leftJoin('places p','p.uuid = o.places_uuid')
            ->leftJoin('invoice_title t','t.uuid = i.invoice_title_uuid')
            ->where('i.uuid',$uuid)
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
        $model = new InvoiceModel();
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

        if (empty($list)) {
            return ['status'=>0,'msg'=>'没内容'];
        }
        $model = new InvoiceModel();
        $data = [];
        $data[] = ['优惠券名称', '优惠券类型','所属活动','内容','限制','有效期开始时间','有效期结束时间','已领取','已使用','已过期','库存'];
        foreach ($list as $k => $vo) {
            $tmp = [$vo['name'],$model->getTypeCn($vo['type']),$model->getActivityCn($vo['activity']),
                $vo['discount'],$model->getOrderTypeCn($vo['order_type']),$vo['start_time'],$vo['end_time'],$vo['receive_num'],$vo['finish_num'],$vo['overdue_num'],$vo['stock']
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
            
            $file_name = '优惠券数据.xlsx';
            $file_path = './excel/'.$file_name;
            $excel_writer->save($file_path);
            if (!file_exists($file_path)) {
                throw new \Exception("Excel生成失败");
            }
            $result = uploadFileExcel($file_name,$file_path);
            return $result;
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    /**
     * 导入Excel
     * @Author   cch
     * @DateTime 2020-05-26T17:24:19+0800
     * @param    $uuid 用户UUID
     * @return   [description]
     */
    public function importExcel(){
        $file = $_FILES['file'];
        if (empty($file)) {
            return ['status'=>0,'msg'=>'未检测到文件'];
        }
        $extension = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objExcel = $objReader->load($file['tmp_name']);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objExcel = $objReader->load($file['tmp_name']);
        }

        $list=$objExcel->getsheet(0)->toArray();   //转换为数组格式
        array_shift($list);  //删除第一个数组(标题);

        Db::startTrans();
        try{
            $save_datas = [];
            foreach ($list as $k => $vo) {
                if (empty($vo[0])) {
                    continue;
                }
                $save_data = [
                    'uuid'=>uuidCreate(),
                    'name'=>$vo[0],
                    'start_time'=>$vo[5],
                    'end_time'=>$vo[6],
                    'discount'=>$vo[3],
                    'limit_num'=>$vo[7],
                    'create_time'=>date('Y-m-d H:i:s')
                ];
                switch ($vo[1]) {
                    case '折扣券': $save_data['type'] = 0; break;
                    case '代金券': $save_data['type'] = 1; break;
                }
                switch ($vo[2]) {
                    case '邀请活动': $save_data['activity'] = 0; break;
                    case '新人活动': $save_data['activity'] = 1; break;
                    default: $save_data['activity'] = 2; break;
                }
                switch ($vo[4]) {
                    case '图片': $save_data['order_type'] = 0; break;
                    case '实物': $save_data['order_type'] = 1; break;
                    default: $save_data['order_type'] = -1; break;
                }
                $fields = ['name'=>'名称'];
                $checks = checkParam($save_data,$fields);
                if (!empty($checks['error_msg'])) {
                    throw new \Exception($checks['error_msg']);
                }
                
                $save_datas[] = $save_data;
            }
            if ( !ActivityModel::insertAll($save_datas) ) {
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
}
