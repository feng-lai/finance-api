<?php
namespace app\api\logic\mini;
use app\api\model\Signs as SignsModel;
use app\api\model\Activity as ActivityModel;
use app\api\model\User as UserModel;
use app\api\model\Notify as NotifyModel;
use think\Db;


class Signs
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new SignsModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->field('s.uuid,a.name,a.start_time,a.end_time,a.sign_start_time,a.sign_end_time,s.status as s_status,s.activity_uuid,s.sign_time')
            ->alias('s')
            ->leftjoin('activity a','a.uuid = s.activity_uuid')
            ->where($map)
            ->order('s.create_time','desc')
            ->paginate($page_param);
        foreach($list as $k=>$v){
            $time = date('Y-m-d H:i:s',time());
            if($v->s_status == 0){
                $list[$k]->status = 1;
            }elseif($v->s_status == 1){
                if($v->start_time > $time){
                    $list[$k]->status = 2;
                }
                if($v->end_time >= $time &&  $time >= $v->start_time ){
                    $list[$k]->status = 3;
                }
                if($v->end_time <= $time){
                    $list[$k]->status = 4;
                }
            }elseif($v->s_status == -1){
                $list[$k]->status = 5;
            }
            unset($v->s_status);
            $list[$k]->start_time = date('Y/m/d H:i',strtotime($v->start_time));
            $list[$k]->end_time = date('Y/m/d H:i',strtotime($v->end_time));
            $list[$k]->sign_start_time = date('Y/m/d H:i',strtotime($v->start_time));
            $list[$k]->sign_end_time = date('Y/m/d H:i',strtotime($v->start_time));
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
        //报名人数是否上限
        $info = ActivityModel::where('uuid',input('activity_uuid'))->find();
        if(!$info->num){
            throw new \Exception("活动不存在");
        }
        if($info->num <= SignsModel::where('activity_uuid',input('activity_uuid'))->count()){
            throw new \Exception("报名人数已满");
        }
        if(!UserModel::where('uuid',input('user_uuid'))->find()){
            throw new \Exception("用户不存在");
        }
        if(SignsModel::where(['user_uuid'=>input('user_uuid'),'activity_uuid'=>input('activity_uuid')])->count()){
            throw new \Exception("不能重复报名");
        }
        //是否在报名时间
        $time = date('Y-m-d H:i:s',time());
        if($time > $info->sign_end_time || $time < $info->sign_start_time){
            throw new \Exception("不在报名时间内");
        }
        //是否可报名
        if($info->type == 0){
            throw new \Exception("该活动不能报名");
        }
        $model = new SignsModel();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['uuid'] = uuidCreate();
            $save_data['user_uuid'] = input('user_uuid');
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
            }
            //后台通知
            NotifyModel::create([
                'uuid'=>uuidCreate(),
                'content'=> '用户'.UserModel::where('uuid',input('user_uuid'))->value('nickname').'申请参加活动'
            ]);
            // 更新成功 提交事务
            Db::commit();
            //订阅消息
            $app = app('wechat.mini_program');
            $data = [
                'template_id' => 'tkUXcX48m9Lnd3FbJhmHTWiZcN9BunsHnVIf4tLobiY', // 所需下发的订阅模板id
                'touser' => UserModel::where('uuid',input('user_uuid'))->value('openid'),     // 接收者（用户）的 openid
                //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
                'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                    'thing1' => [
                        'value' => ActivityModel::where('uuid',input('activity_uuid'))->value('name'), //活动名称
                    ],
                    'thing10' => [
                        'value' => input('name'), //姓名
                    ],
                    'phone_number11' => [
                        'value' => input('mobile'), //电话
                    ],
                    'phrase4' => [
                        'value' => '待审核', //审核状态
                    ],
                    'date2' => [
                        'value' => date('Y-m-d H:i:s'), //提交时间
                    ],
                ],
            ];
            $app->subscribe_message->send($data);
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
    public function updateData($uuid){
        if(!SignsModel::where(['activity_uuid'=>$uuid,'user_uuid'=>input('user_uuid'),'status'=>1])->count()){
            throw new \Exception("该用户还没报名");
        }
        if(SignsModel::where(['activity_uuid'=>$uuid,'user_uuid'=>input('user_uuid'),'status'=>1])->value('sign_time')){
            throw new \Exception("该用户已签到");
        }
        $model = new SignsModel();
        // 启动事务 
        Db::startTrans();
        try{
            if ($model->where(['activity_uuid'=>$uuid,'user_uuid'=>input('user_uuid'),'status'=>1])->update(['sign_time'=>date('Y-m-d H:i:s',time())]) === false ) {
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
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @return         [description]
     */
    public function read($uuid){
        $model = new SignsModel();
        $data = $model->field('s.*,a.name as activity_name,u.nickname,u.mobile as phone')
            ->alias('s')
            ->leftjoin('activity a','a.uuid = s.activity_uuid')
            ->leftjoin('user u','u.uuid = s.user_uuid')
            ->where('s.uuid',$uuid)->find();
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
        $model = new SignsModel();
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
        $model = new SignsModel();
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
