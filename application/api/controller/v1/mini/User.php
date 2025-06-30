<?php
namespace app\api\controller\v1\mini;
use app\api\controller\v1\mini\Base;
use app\api\logic\mini\User as UserLogic;
use app\api\model\User as Usermodel;
use EasyWeChat\Factory;
use think\Db;

class User extends Base
{
    protected $noCheckToken = ['login','mobile'];
    /**
     * 登录
     * @Author   cch
     * @DateTime 2020-05-29T12:06:40+0800
     * @return             [description]
     */
    public function login(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        $fields = [
            'must'=>['code'],
            'nomust'=>['mobile','headimgurl']
        ];
        $params = paramFilter($param,$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }

        $logic = new UserLogic();
        $result = $logic->login($params);
        if ($result['status'] == 1) {
            return apiResult('2000','提交成功',$result['data']);
        }else{
            return apiResult('5000',$result['msg']);
        }
    }

    //获取手机号
    public function mobile(){
        checkInputEmptyByExit(['code']);
        $app = app('wechat.mini_program');
        $res = $app->phone_number->getUserPhoneNumber(input('code'));
        if ($res['errcode'] == 0) {
            return apiResult('2000','成功',$res['phone_info']['phoneNumber']);
        }else{
            return apiResult('5000',$res['errmsg']);
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
        $fields = [
            'nomust'=>['mobile','nickname','headimgurl','age','gender']
        ];
        $save_data = paramFilter($param,$fields);
        if (empty($save_data)) {
            return $this->apiResult('5000','无任何更改');
        }
        $logic = new UserLogic();
        $result = $logic->update(input('user_uuid'),$save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','更新成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 获取用户详情
     * @Author   cch
     * @DateTime 2020-05-26T17:22:45+0800
     * @return   [type]                   [description]
     */
    public function read(){
        $logic = new UserLogic();
        $data = $logic->read(input('user_uuid'));
        return $this->apiResult('2000','获取成功',$data);
    }

    /**
     * 删除
     * @Author   CCH
     * @DateTime 2020-05-30T15:27:04+0800
     * @return   [type]                   [description]
     */
    public function delete($uuid){
        $logic = new UserLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }


    

}
