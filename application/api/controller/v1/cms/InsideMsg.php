<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\InsideMsg as InsideMsgLogic;
use think\Db;


class InsideMsg extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $map_params = [
            ['key'=>'status','type'=>'='],
            ['key'=>'location','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $search_type = input('search_type','title');
            $map[] = [$search_type,'like','%'.input('keyword_search').'%'];
        }
        if (isSearchParam('start_time')) {
            $map[] = ['create_time','>=',input('start_time')];
        }
        if (isSearchParam('end_time')) {
            $map[] = ['create_time','<=',input('end_time')];
        }
        $logic = new InsideMsgLogic();
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
            'must'=>['title','content','location','status'],
            'nomust'=>['send_time']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }
        $save_data['admin_uuid'] = input('login_admin_uuid');

        $logic = new InsideMsgLogic();
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
    // public function update(){
    //     $param = file_get_contents('php://input');
    //     $param = json_decode($param,true);
    //     if (empty($param['uuid'])) {
    //         exception('uuid不能为空',400);
    //     }

    //     $fields = [
    //         'nomust'=>['title','content']
    //     ];
    //     $save_data = paramFilter($param,$fields);
    //     if (empty($save_data)) {
    //         return $this->apiResult('5000','无任何更改');
    //     }
        
    //     $logic = new InsideMsgLogic();
    //     $result = $logic->updateData($param['uuid'],$save_data);
    //     if ($result['status'] == 1) {
    //         return $this->apiResult('2000','更新成功');
    //     }else{
    //         return $this->apiResult('5000',$result['msg']);
    //     }
    // }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-26T17:22:45+0800
     * @return   [type]                   [description]
     */
    public function read($uuid){
        $logic = new InsideMsgLogic();
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
        $logic = new InsideMsgLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    // 消息名单
    public function userInsideMsgs(){
        $map_params = [
            ['key'=>'status','type'=>'='],
            ['key'=>'inside_msg_uuid','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        $logic = new InsideMsgLogic();
        $list = $logic->userInsideMsgs($map);
        return $this->apiResult('2000','获取成功',$list);
    }

}
