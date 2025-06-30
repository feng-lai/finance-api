<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Admin as AdminLogic;


class Admin extends Base
{

    protected $noCheckToken = ['getCaptcha','login','getSmsCaptcha','smsLogin'];

    /**
     * 获取验证码
     * @Author   cch
     * @DateTime 2020-05-29T12:06:30+0800
     * @param    $code 验证码标识
     * @return         [description]
     */
    public function getCaptcha($code){
        $logic = new AdminLogic();
        $result = $logic->getCaptcha($code);
        return $result;
        // if ($result['status'] == 1) {
        //     return $this->apiResult('2000','获取成功',$result['data']);
        // }else{
        //     return $this->apiResult('5000',$result['msg']);
        // }
    }

    /**
     * 管理员登录
     * @Author   cch
     * @DateTime 2020-05-29T12:06:40+0800
     * @param    $account  [description]
     * @param    $password [description]
     * @param    $code     验证码标识
     * @param    $verify   验证码
     * @return             [description]
     */
    public function login(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        $fields = [
            'must'=>['account','password']
        ];
        $params = paramFilter($param,$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }

        $logic = new AdminLogic();
        $result = $logic->login($params['account'],$params['password']);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','获取成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }


    /**
     * 管理员登出
     * @Author   cch
     * @DateTime 2020-05-29T12:06:40+0800
     * @return             [description]
     */
    public function logout(){
        $uuid = input('login_admin_uuid');
        if (empty($uuid)) {
            exception('管理员UUID不能为空',400);
        }
        $logic = new AdminLogic();
        $result = $logic->logout($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','登出成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   列表
     */
    public function index(){
        $map = [];
        if (isSearchParam('keyword_search')) {
            $search_type = input('search_type','truename');
            $map[] = [$search_type,'like','%'.input('keyword_search').'%'];
        }
        $logic = new AdminLogic();
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
        $logic = new AdminLogic();
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
            'must'=>['account','password','role_uuid'],
            'nomust'=>['truename','mobile']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        $logic = new AdminLogic();
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
            'nomust'=>['account','password','role_uuid','truename','mobile']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }
        
        $logic = new AdminLogic();
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
        $logic = new AdminLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 获取登录账号详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:40+0800
     * @param    $uuid [description]
     * @param    $lat  [description]
     * @param    $lng  [description]
     * @return         [description]
     */
    public function getLoginInfo(){
        $uuid = input('login_admin_uuid');
        $logic = new AdminLogic();
        $data = $logic->getLoginInfo($uuid);
        return $this->apiResult('2000','获取成功',$data);
    }

    
}
