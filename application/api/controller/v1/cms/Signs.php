<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Signs as SignsLogic;

class Signs extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $map = getSearchParam($map_params);
        if (isSearchParam('status')) {
            $map[] = ['s.status','=',input('status')];
        }
        if (isSearchParam('keyword_search')) {
            $map[] = ['a.name','like','%'.input('keyword_search').'%'];
        }
        if(input('activity_time')){
            $map[] = ['a.create_time','>=',date('Y-m-d',strtotime(input('activity_time')))];
            $map[] = ['a.create_time','<=',date('Y-m-d',strtotime(input('activity_time').'+1 day'))];
        }
        if(input('activity_uuid')){
            $map[] = ['a.uuid','=',input('activity_uuid')];
        }
        if(input('user_uuid')){
            $map[] = ['s.user_uuid','=',input('user_uuid')];
        }
        if(input('is_sign') == 1){
            $map[] = ['s.sign_time','<>','null'];
        }
        $logic = new SignsLogic();
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
            'must'=>['activity_uuid','user_uuid','name','mobile','company','position'],
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        $logic = new SignsLogic();
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
            'nomust'=>['activity_uuid','user_uuid','status','name','mobile','company','position'],
        ];
        $save_data = paramFilter($param,$fields);
        if (empty($save_data)) {
            return $this->apiResult('5000','无任何更改');
        }
        
        $logic = new SignsLogic();
        $result = $logic->updateData($param['uuid'],$save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','更新成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    public function batch(){
        checkInputEmptyByExit(['data']);
        $logic = new SignsLogic();
        $result = $logic->batch(input('data'));
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
        $logic = new SignsLogic();
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
        $logic = new SignsLogic();
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
        $map = getSearchParam($map_params);
        if (isSearchParam('status')) {
            $map[] = ['s.status','=',input('status')];
        }
        if (isSearchParam('keyword_search')) {
            $map[] = ['a.name','like','%'.input('keyword_search').'%'];
        }
        if(input('activity_time')){
            $map[] = ['a.create_time','>=',date('Y-m-d',strtotime(input('activity_time')))];
            $map[] = ['a.create_time','<=',date('Y-m-d',strtotime(input('activity_time').'+1 day'))];
        }
        if(input('activity_uuid')){
            $map[] = ['a.uuid','=',input('activity_uuid')];
        }
        if(input('user_uuid')){
            $map[] = ['s.user_uuid','=',input('user_uuid')];
        }
        if(input('is_sign') == 1){
            $map[] = ['s.sign_time','<>','null'];
        }
        $logic = new SignsLogic();
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
        $logic = new SignsLogic();
        $result = $logic->importExcel();
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

}
