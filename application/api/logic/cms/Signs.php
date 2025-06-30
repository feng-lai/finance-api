<?php
namespace app\api\logic\cms;
use app\api\model\Signs as SignsModel;
use app\api\model\Activity as ActivityModel;
use app\api\model\User as UserModel;
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
        $list = $model->field('s.*,a.name as activity_name,a.start_time,a.end_time')
            ->alias('s')
            ->leftjoin('activity a','a.uuid = s.activity_uuid')
            ->where($map)
            ->order('s.create_time','desc')
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
        //报名人数是否上限
        $num = ActivityModel::where('uuid',input('activity_uuid'))->value('num');
        if(!$num){
            throw new \Exception("活动不存在");
        }
        if($num <= SignsModel::where('activity_uuid',input('activity_uuid'))->count()){
            throw new \Exception("报名人数已满");
        }
        if(!UserModel::where('uuid',input('user_uuid'))->find()){
            throw new \Exception("用户不存在");
        }
        if(SignsModel::where(['user_uuid'=>input('user_uuid'),'activity_uuid'=>input('activity_uuid')])->count()){
            throw new \Exception("已经报名了");
        }
        $model = new SignsModel();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['uuid'] = uuidCreate();
            
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
        $data = SignsModel::where('uuid',$uuid)->find();
        $model = new SignsModel();
        // 启动事务 
        Db::startTrans();
        try{
            if ($model->where('uuid',$uuid)->update($save_data) === false ) {
                throw new \Exception("保存失败");
            }
            // 更新成功 提交事务
            Db::commit();
            if(input('status') == 0){
                return ['status'=>1];
            }
            if(!UserModel::where('uuid',$data->user_uuid)->value('openid')){
                return ['status'=>1];
            }
            //订阅消息
            $app = app('wechat.mini_program');
            $data = [
                'template_id' => 'rWYPTo8k0gvns1HHs7jInXXI-OQKfFgufpsJpdmOVOk', // 所需下发的订阅模板id
                'touser' => UserModel::where('uuid',$data->user_uuid)->value('openid'),     // 接收者（用户）的 openid
                //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
                'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                    'thing2' => [
                        'value' => ActivityModel::where('uuid',$data->activity_uuid)->value('name'), //活动名称
                    ],
                    'name4' => [
                        'value' => $data->name, //申请人
                    ],
                    'time11' => [
                        'value' => date('Y-m-d H:i:s'), //审核时间
                    ],
                    'phrase1' => [
                        'value' => input('status') == 1?'审核通过':'审核不通过', //审核结果
                    ],
                ],
            ];
            $app->subscribe_message->send($data);
            return ['status'=>1];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    public function batch($data){
        $data = json_decode($data,true);
        foreach($data as $v){
            SignsModel::where('uuid',$v['uuid'])->update(['status'=>$v['status']]);
            $info = SignsModel::where('uuid',$v['uuid'])->find();
            //订阅消息
            $app = app('wechat.mini_program');
            $data = [
                'template_id' => 'rWYPTo8k0gvns1HHs7jInXXI-OQKfFgufpsJpdmOVOk', // 所需下发的订阅模板id
                'touser' => UserModel::where('uuid',$info->user_uuid)->value('openid'),     // 接收者（用户）的 openid
                //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
                'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                    'thing2' => [
                        'value' => ActivityModel::where('uuid',$info->activity_uuid)->value('name'), //活动名称
                    ],
                    'name4' => [
                        'value' => $info->name, //申请人
                    ],
                    'time11' => [
                        'value' => date('Y-m-d H:i:s'), //审核时间
                    ],
                    'phrase1' => [
                        'value' => input('status') == 1?'审核通过':'审核不通过', //审核结果
                    ],
                ],
            ];
            $app->subscribe_message->send($data);
        }
        return ['status'=>1];
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
        $data = $model->field('s.*,a.name as activity_name,u.nickname,u.mobile as phone,a.start_time,a.end_time')
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
        $list = $this->getList($map)->toArray();
        $list = $list['data'];

        if (empty($list)) {
            return ['status'=>0,'msg'=>'没内容'];
        }
        $model = new SignsModel();
        $data = [];

        if(input('type') == 2){
            $data[] = ['序号', '用户昵称','联系电话','签到时间'];
        }else{
            $data[] = ['排列序号', '活动标题','报名人','联系电话','报名时间','审核状态'];
        }
        foreach ($list as $k => $vo) {
            if(input('type') == 2){
                $tmp = [
                    $k+1,
                    $vo['name'],
                    ' '.$vo['mobile'].' ',
                    $vo['sign_time']
                ];
            }else{
                $text = '审核中';
                if($vo['status'] == 1){
                    $text = '审核通过';
                }
                if($vo['status'] == -1){
                    $text = '审核不通过';
                }
                $tmp = [
                    $k+1,
                    $vo['activity_name'],
                    $vo['name'],
                    ' '.$vo['mobile'].' ',
                    $vo['create_time'],
                    $text
                ];
            }
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
            
            $file_name = '报名数据.xlsx';
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
