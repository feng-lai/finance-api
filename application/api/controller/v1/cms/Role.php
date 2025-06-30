<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Role as RoleLogic;
use think\Db;

class Role extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   列表
     */
    public function index(){
        $map = [];
        if (isSearchParam('keyword_search')) {
            $search_type = input('search_type','name');
            $map[] = [$search_type,'like','%'.input('keyword_search').'%'];
        }
        $logic = new RoleLogic();
        $list = $logic->getList($map,$page_param);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:40+0800
     * @param    $uuid [description]
     * @param    $lat  [description]
     * @param    $lng  [description]
     * @return         [description]
     */
    public function read($uuid){
        $logic = new RoleLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
    }

    /**
     * 保存员工
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function save(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        $fields = [
            'must'=>['name','menu_uuids']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        $logic = new RoleLogic();
        $result = $logic->saveData($save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','提交成功',$result['uuid']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 更新员工
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
            'nomust'=>['name','menu_uuids']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        if (empty($save_data)) {
            return $this->apiResult('5000','无任何更改');
        }
        
        $logic = new RoleLogic();
        $result = $logic->updateData($param['uuid'],$save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','更新成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 删除
     * @Author   CCH
     * @DateTime 2020-05-30T15:27:04+0800
     * @return   [type]                   [description]
     */
    public function delete($uuid){
        $logic = new RoleLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }
}
