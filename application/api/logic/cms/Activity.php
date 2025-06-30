<?php
namespace app\api\logic\cms;
use app\api\model\Activity as ActivityModel;
use app\api\model\Signs as SignsModel;
use think\Db;


class Activity
{
    public function getImg($code){
        require_once "../extend/phpqrcode.php"; //加载第三方库
        $errorCorrectionLevel = 'H';  //容错级别
        $matrixPointSize = 10;      //生成图片大小
        $margin=2;
        //生成二维码图片
        // 判断是否有这个文件夹  没有的话就创建一个
        if(!is_dir("qrcode")){
            // 创建文件夹
            mkdir("qrcode");
        }
        //设置二维码文件名
        $filename = 'qrcode/'.time().rand(10000,9999999).'.png';
        //生成二维码
        \QRcode::png($code,$filename , $errorCorrectionLevel, $matrixPointSize, $margin);

        //以下是二维码中带上logo,根据需要使用
        $logo = 'logo.png';//准备好的logo图片
        $QR = $filename;//已经生成的原始二维码图
        if ($logo !== FALSE) {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                $logo_qr_height, $logo_width, $logo_height);
        }
        imagepng ($QR, $filename );//重新生成二维码图片

        return $filename;
    }
    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new ActivityModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time','desc')->paginate($page_param);
        foreach($list as $k=>$v){
            $list[$k]->sign = SignsModel::where('activity_uuid',$v->uuid)->count();
            $list[$k]->signIn = SignsModel::where('activity_uuid',$v->uuid)->whereNotNull('sign_time')->count();
            $time = date('Y-m-d H:i:s',time());
            if($v->start_time >= $time){
                $list[$k]->status = 1;
            }
            if($v->sign_start_time <= $time && $v->sign_end_time >= $time){
                $list[$k]->status = 2;
            }
            if($v->start_time <= $time && $v->end_time >= $time){
                $list[$k]->status = 3;
            }
            if($v->end_time <= $time){
                $list[$k]->status = 4;
            }
            if($v->type == 0){
                $list[$k]->status = 5;
            }
        }
        return $list;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new ActivityModel();
        // 启动事务 
        Db::startTrans();
        try{
            $uuid = uuidCreate();
            $save_data['uuid'] = $uuid;
            $img = $this->getImg($uuid);
            $path = \Env::get('root_path').'public';
            $result = uploadFile($img,$path.'/'.$img,'xmh_motion/');
            if($result['status'] == 1){
                unlink($path.'/'.$img);
                $save_data['code'] = $result['data'];
            }

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
        $model = new ActivityModel();
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
        $model = new ActivityModel();
        $data = $model->where('uuid',$uuid)->find();
        $data['sign'] = SignsModel::where('activity_uuid',$data->uuid)->count();
        $data['signIn'] = SignsModel::where('activity_uuid',$data->uuid)->whereNotNull('sign_time')->count();
        $time = date('Y-m-d H:i:s',time());
        if($data->sign_start_time <= $time && $data->sign_end_time >= $time){
            $data['status'] = 1;
        }
        if($data->start_time <= $time && $data->end_time >= $time){
            $data['status'] = 2;
        }
        if($data->end_time <= $time){
            $data['status'] = 3;
        }
        if($data->type == 0){
            $data['status'] = 4;
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
        $model = new ActivityModel();
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
        $model = new ActivityModel();
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
