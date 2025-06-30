<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Activity as ActivityLogic;

class Activity extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $map_params = [
            ['key'=>'type','type'=>'='],
            ['key'=>'is_show','type'=>'='],
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $map[] = ['name','like','%'.input('keyword_search').'%'];
        }
        if(input('activity_time')){
            $map[] = ['start_time','<=',input('activity_time')];
            $map[] = ['end_time','>=',input('activity_time')];
        }
        if(input('status')){
            $time = date('Y-m-d H:i:s',time());
            switch (input('status')){
                case 1://待开始
                    $map[] = ['start_time','>=',$time];
                    $map[] = ['type','=',1];
                    break;
                case 2://报名中
                    $map[] = ['sign_start_time','<=',$time];
                    $map[] = ['sign_end_time','>=',$time];
                    $map[] = ['type','=',1];
                    break;
                case 3://活动中
                    $map[] = ['start_time','<=',$time];
                    $map[] = ['end_time','>=',$time];
                    $map[] = ['type','=',1];
                    break;

                case 4://已结束
                    $map[] = ['end_time','<=',$time];
                    $map[] = ['type','=',1];
                    break;

                case 5://仅展示
                    $map[] = ['type','=',0];
                    break;
            }
        }
        $logic = new ActivityLogic();
        $list = $logic->getList($map);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 保存
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function save(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        $fields = [
            'must'=>['name','start_time','end_time','description','image','address','type'],
            'nomust'=>['sign_start_time','num','sign_end_time']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }
        $logic = new ActivityLogic();
        $result = $logic->saveData($save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','提交成功',$result['uuid']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 更新
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function update(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        if (empty($param['uuid'])) {
            exception('uuid不能为空',400);
        }

        $fields = [
            'nomust'=>['name','start_time','end_time','sign_start_time','sign_end_time','description','image','address','num','type','is_show'],
        ];
        $save_data = paramFilter($param,$fields);
        if (empty($save_data)) {
            return $this->apiResult('5000','无任何更改');
        }
        
        $logic = new ActivityLogic();
        $result = $logic->updateData($param['uuid'],$save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','更新成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-26T17:22:45+0800
     * @return   [type]                   [description]
     */
    public function read($uuid){
        $logic = new ActivityLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
    }

    /**
     * 删除
     * @Author   CCH
     * @DateTime 2020-05-30T15:27:04+0800
     * @return   [type]                   [description]
     */
    public function delete($uuid){
        $logic = new ActivityLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 导出Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:04:48+0800
     * @return   excel下载地址
     */
    public function export(){
        $map_params = [
            ['key'=>'status','type'=>'='],
            ['key'=>'type','type'=>'='],
            ['key'=>'order_type','type'=>'='],
            ['key'=>'activity','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $map[] = ['name','like','%'.input('keyword_search').'%'];
        }
        $logic = new ActivityLogic();
        $result = $logic->exportExcel($map);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 导入Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:04:48+0800
     * @return   excel下载地址
     */
    public function import(){
        $logic = new ActivityLogic();
        $result = $logic->importExcel();
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

}
