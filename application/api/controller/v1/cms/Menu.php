<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Menu as MenuLogic;
use think\Db;

class Menu extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   预约列表
     */
    public function index(){
        $map_params = [
            ['key'=>'parent_uuid','type'=>'='],
            ['key'=>'level','type'=>'='],
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $search_type = input('search_type','name');
            $map[] = [$search_type,'like','%'.input('keyword_search').'%'];
        }
        if(!input('parent_uuid')){
            $map[] = ['parent_uuid','=',''];
        }
        $logic = new MenuLogic();
        $list = $logic->getList($map,$page_param);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 获取预约详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:40+0800
     * @param    $uuid [description]
     * @param    $lat  [description]
     * @param    $lng  [description]
     * @return         [description]
     */
    public function read($uuid){
        $logic = new MenuLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
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
            'must'=>['url','name'],
            'nomust'=>['parent_uuid','sort']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        $logic = new MenuLogic();
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
            'nomust'=>['url','name','parent_uuid','sort']
        ];
        $save_data = paramFilter($param,$fields);
        if (empty($save_data)) {
            return $this->apiResult('5000','无任何更改');
        }

        if (empty($save_data)) {
            return $this->apiResult('5000','无任何更改');
        }
        $logic = new MenuLogic();
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
        $logic = new MenuLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    public function many(){
        checkInputEmptyByExit('data');
        $logic = new MenuLogic();
        $result = $logic->many(input('data'));
        if ($result['status'] == 1) {
            return $this->apiResult('2000','成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }
}
