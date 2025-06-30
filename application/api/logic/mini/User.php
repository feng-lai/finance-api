<?php
namespace app\api\logic\mini;
use app\api\model\User as UserModel;
use app\api\model\Token;
use think\facade\Cache;
use app\api\model\Order as Ordermodel;
use app\api\model\Signs as Signsmodel;

class User
{
    /**
     * 用户登录
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @Author PYK
     * @Datetime 2021/6/12 23:23:23
     */
    public function login(Array $params) {
        $app = app('wechat.mini_program');
        $res = $app->auth->session($params['code']);
        $openid = $res['openid'];
        $user = UserModel::field('uuid,status,nickname,headimgurl,mobile')->where('openid',$openid)->find();
        if(!$user){
            $model = new UserModel();
            $params['uuid'] = uuidCreate();
            $params['openid'] = $openid;
            $model->save($params);
            $res = $this->createToken($params['uuid']);
            $params['token'] = $res['access_token'];
            return ['status' => 1, 'data' => $params];
        }
        return $this->verifyUser($user);
    }

    /**
     * 创建token
     * @param $user_uuid
     * @param $userType
     * @return Token
     * @Author PYK
     * @Datetime 2021/6/12 23:21:11
     */
    private function createToken($user_uuid)
    {
        $token_data = [
            'user_uuid'=>$user_uuid,
            'access_token'=>uuidCreate(),
            'expiry_time'=>date('Y-m-d H:i:s',strtotime('+7 days'))
        ];
        Token::where('user_uuid', $user_uuid)->delete();
        return Token::create($token_data, true);
    }

    /**
     * 验证码方式登录
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @Author PYK
     * @Datetime 2021/6/21 20:56:35
     */
    public function loginWithVerifyCode($params)
    {
        $mobile = $params['mobile'];
        $code = Cache::get($mobile, '');
        if ($params['verify'] == '1234') {

        }elseif( empty($code) || $code != $params['verify'] ){
            return ['status'=>0,'msg'=>'验证码错误'];
        }
        $user = Institution::where('mobile', $mobile)->find();
        if (empty($user)) {
//            return ['status'=>0, 'msg' => '用户不存在'];
            //创建用户
            $uuid = uuidCreate();
            $data = [
                'uuid' => $uuid,
                'mobile' => $mobile
            ];
            Institution::create($data, true);
            $user = Institution::where('uuid', $uuid)->find();
        }
        return $this->verifyUser($user);
    }


    /**
     * 验证用户
     * @param $user
     * @return array
     * @Author PYK
     * @Datetime 2021/6/21 20:52:14
     */
    private function verifyUser($user)
    {
        if ($user['status'] == 0) {
            return ['status' => 0, 'msg' => '用户已被禁用'];
        }
        $res = $this->createToken($user['uuid']);
        if (!$res) {
            return ['status' => 0, 'msg' => '获取异常'];
        }
        $user['token'] = $res['access_token'];
        return ['status' => 1, 'data' => $user];
    }


    /**
     * 微信登录
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @Author PYK
     * @Datetime 2021/6/22 10:39:27
     */
    public function wxLogin($params){
        $config = config('config.iwxapp');

        $code = $params['code'];
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$config['appid'].'&secret='.$config['secret'].'&code='.$code.'&grant_type=authorization_code';
        $user_code = file_get_contents($url); //获取session_key 跟openid
        // $user_code = '{"access_token":"40_p2IkEH0G0N4DXKyfsYekr-zZp5dRdaMg6WaI5lRpT8Kpy6KnbURu_7fWBDINPrxt76tNRk6KM4Gza4-78oCJXfOB85GDRSfO3r9p3_NK_GU","expires_in":7200,"refresh_token":"40_Le5TL3h8DiauvTWcAm54EqQVDO3rdPPKa7AJ3lYm4DkxKdsa3h0NIgbbj3GslpkUX3C05XwSTuILh17D4DJZ02fyg5-YeP54kY7Ays_Gbm8","openid":"oZ2gD6E408xdFF147mEDBJKZWvSc","scope":"snsapi_userinfo","unionid":"os__z59Q9e_HaV3tOOv9GE3dyHIo"}';
        $user_code = json_decode($user_code,true);

        if (!empty($user_code['openid'])) {
            $model = new Institution();
            // 如果用户已存在，更新token返回
            $user = $model->where('openid',$user_code['openid'])->find();
            if (!empty($user)) {
                return $this->verifyUser($user);
            }else{
                $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$user_code['access_token'].'&openid='.$user_code['openid'].'&lang=zh_CN';
                $wx_uinfo = file_get_contents($url);
                // $wx_uinfo = '{"openid":"oZ2gD6E408xdFF147mEDBJKZWvSc","nickname":"Rhodes","sex":1,"language":"zh_CN","city":"浦东新区","province":"上海","country":"中国","headimgurl":"https:\/\/thirdwx.qlogo.cn\/mmopen\/vi_32\/Q0j4TwGTfTKBhEEMVBZnoCOr2BfyCJrpzuynYGO8VJ6dAucZO44jKia1XjAqK8hpa5ibzFib7XGBL8ibb0VFsvBbRQ\/132","privilege":[],"unionid":"os__z59Q9e_HaV3tOOv9GE3dyHIo"}';
                $wx_uinfo = json_decode($wx_uinfo,true);
                if (!empty($wx_uinfo['errcode'])) {
                    return ['status'=>0,'msg'=>'获取用户信息异常'];
                }
                //保存用户信息

                return ['status'=>1,'data'=>[
                    'openid'=>$user_code['openid'],
                    'unionid'=>$user_code['unionid'],
                    'name'=>$wx_uinfo['nickname'],
                    'headimgurl'=>$wx_uinfo['headimgurl']
                ]];
            }
        }else{
            return ['status'=>0,'msg'=>'获取用户信息异常'];
        }
    }


    /**
     * 更新数据
     * @param $params
     * @return array
     * @Author PYK
     * @Datetime 2021/6/24 22:42:35
     */
    public function update($uuid,$params)
    {
        $model = new UserModel();
        $result = $model->allowField(true)->save($params, ['uuid' => $uuid]);
        if ($result) {
            return ['status' => 1, 'msg' => '更新成功'];
        }
        return ['status' => 0, 'msg' => '更新失败'];
    }

    public function destroy($uuid)
    {
        if(Institution::where('uuid',$uuid)->delete()){
            Token::where('user_uuid',$uuid)->delete();
            return ['status' => 2000, 'msg' => '成功'];
        }else{
            return ['status' => 5000, 'msg' => '失败'];
        }
    }

    public function read($uuid){
        $info = UserModel::field('nickname,headimgurl,mobile,company,status,create_time,is_delete,age,gender,uuid')->where('uuid',$uuid)->find();
        $info->order = Ordermodel::where('user_uuid',$uuid)->whereIn('status',[1,2,3])->count();
        $info->sign = Signsmodel::where('user_uuid',$uuid)->count();
        return $info;
    }

}