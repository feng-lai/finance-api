<?php
namespace app\api\logic\cms;
use app\api\model\Institution as InstitutionModel;
use app\api\model\Order as OrderModel;
use app\api\model\Classes as ClassesModel;

use think\Db;


class Institution
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new InstitutionModel();
        $order_field = input('order_field','create_time');
        $order_mode = input('order_mode','desc');
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order($order_field,$order_mode)->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $map = [
                ['institution_uuid','=',$vo['uuid']]
            ];
            $list['data'][$k]['classes_num'] = ClassesModel::where('institution_uuid',$vo['uuid'])->count();
            $list['data'][$k]['order_num'] = OrderModel::where('user_uuid',$vo['uuid'])->count();
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
        $data[] = ['机构id', '机构名称', '手机号','类型','班级数量','订单数量','状态','注册时间'];
        foreach ($list as $k => $vo) {
            $tmp = [
                $vo['uuid'],$vo['name'],$vo['mobile'],
                InstitutionModel::getTypeCn($vo['type']),
                $vo['classes_num'],$vo['order_num'],
                InstitutionModel::getStatusCn($vo['status']),
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
            
            $file_name = '机构数据.xlsx';
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
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new InstitutionModel();
        // 启动事务 
        Db::startTrans();
        try{
            if ($model->where('mobile',$save_data['mobile'])->count() > 0) {
                throw new \Exception("手机号已存在");
            }
            $save_data['uuid'] = uuidCreate();
            // $save_data['institution_sn'] = numberCreate();
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
        $model = new InstitutionModel();
        $data = $model->where('uuid',$uuid)->find();
        // 启动事务 
        Db::startTrans();
        try{
            if (!empty($save_data)) {
                if (!empty($save_data['mobile']) && $data['mobile'] != $save_data['mobile'] && $model->where('mobile',$save_data['mobile'])->count() > 0) {
                    throw new \Exception("手机号已存在");
                }
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
        $model = new InstitutionModel();
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($data)) {
            $data['classes_num'] = ClassesModel::where('institution_uuid',$data['uuid'])->count();
            $data['order_num'] = OrderModel::where('user_uuid',$data['uuid'])->count();
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
        $model = new InstitutionModel();
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
                
                $save_data = [
                    'uuid'=>uuidCreate(),
                    'institution_sn'=>numberCreate(),
                    'name'=>$vo[0],
                    'truename'=>$vo[1],
                    'mobile'=>$vo[2],
                    'province'=>$vo[3],
                    'city'=>$vo[4],
                    'address'=>$vo[5],
                    'create_time'=>date('Y-m-d H:i:s')
                ];
                $fields = ['name'=>'名称','mobile'=>'手机'];
                $checks = checkParam($save_data,$fields);
                if (!empty($checks['error_msg'])) {
                    throw new \Exception($checks['error_msg']);
                }
                if ( InstitutionModel::where('mobile',$save_data['mobile'])->count() > 0 ) {
                    throw new \Exception("手机号已存在");
                }
                
                $save_datas[] = $save_data;
            }
            if ( !InstitutionModel::insertAll($save_datas) ) {
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
