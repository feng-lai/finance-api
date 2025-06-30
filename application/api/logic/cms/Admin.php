<?php
namespace app\api\logic\cms;
use app\api\model\Admin as AdminModel;
use app\api\model\AdminToken as AdminTokenModel;
use app\api\model\Role as RoleModel;
use app\api\model\Menu as MenuModel;
use app\api\model\Supplier as SupplierModel;

use think\facade\Cache;
use think\captcha\Captcha;
use think\Db;


class Admin
{
    /**
     * 获取验证码
     * @Author   cch
     * @DateTime 2020-05-29T12:06:30+0800
     * @param    $code 验证码标识
     * @return         [description]
     */
    public function getCaptcha($code){
   //      $token_data = [
			// 'access_token'=>uuidCreate(),
			// 'expiry_time'=>date('Y-m-d H:i:s',strtotime('+7 days')),
   //      ];
   //      if ( AdminTokenModel::insert($token_data) ) {
   //      	$captcha = new Captcha();
   //      	$tmp = $captcha->entry($token_data['access_token']);
	  //       $token_data['image'] = $tmp->getData();
	  //       return ['status'=>1,'data'=>$token_data];
   //      }else{
   //      	return ['status'=>0,'msg'=>'生成验证码失败'];
   //      }
        $captcha = new Captcha();
        $data = $captcha->entry($code);
        return $data->getData();
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
    public function login($account,$password){
        $model = new AdminModel();
        $data = $model->where('account',$account)->find();
        if (empty($data)) {
            return ['status'=>0,'msg'=>'管理员不存在'];
        }elseif($data['password'] != md5($password)){
            return ['status'=>0,'msg'=>'管理员密码错误'];
        }
        // elseif (!empty($data['valid_time']) && $data['valid_time'] < date('Y-m-d H:i:s')) {
        //     return ['status'=>0,'msg'=>'账号已过有效期'];
        // }

        $token = AdminTokenModel::where('admin_uuid',$data['uuid'])->find();
        if (!empty($token)) {
            $token_data = [
                'admin_uuid'=>$data['uuid'],
                'access_token'=>$token['access_token'],
                'expiry_time'=>date('Y-m-d H:i:s',strtotime('+7 days'))
            ];
        }else{
            $token_data = [
                'admin_uuid'=>$data['uuid'],
                'access_token'=>uuidCreate(),
                'expiry_time'=>date('Y-m-d H:i:s',strtotime('+7 days'))
            ];
        }
        AdminTokenModel::where('admin_uuid',$token_data['admin_uuid'])->delete();
        if ( AdminTokenModel::insert($token_data) ) {
            // 更新最后登录时间
            $model->where('uuid',$data['uuid'])->update(['last_login_time'=>date('Y-m-d H:i:s')]);

            return ['status'=>1,'data'=>$token_data];
        }else{
            return ['status'=>0,'msg'=>'获取异常'];
        }
    }

    /**
     * 管理员登出
     * @Author   cch
     * @DateTime 2020-05-29T12:06:40+0800
     * @param    $account  [description]
     * @param    $password [description]
     * @param    $code     验证码标识
     * @param    $verify   验证码
     * @return             [description]
     */
    public function logout($uuid){
        if ( AdminTokenModel::where('admin_uuid',$uuid)->delete() ) {
            return ['status'=>1];
        }else{
            return ['status'=>0,'msg'=>'清楚token异常'];
        }
    }

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new AdminModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time','desc')->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['role_name'] = RoleModel::where('uuid',$vo['role_uuid'])->value('name');
        } 
        return $list;
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @param    $lat  [description]
     * @param    $lng  [description]
     * @return         [description]
     */
    public function read($uuid){
        $model = new AdminModel();
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($data)) {
            $data['role_name'] = RoleModel::where('uuid',$data['role_uuid'])->value('name');
        }
        return $data;
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @param    $lat  [description]
     * @param    $lng  [description]
     * @return         [description]
     */
    public function getLoginInfo($uuid){
        $model = new AdminModel();
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($data)) {
            $role = RoleModel::where('uuid',$data['role_uuid'])->find();
            $role['menu_uuids'] = explode(',', $role['menu_uuids']);

            $tmp = MenuModel::where('uuid','in',$role['menu_uuids'])->where('parent_uuid','=','')->order('sort asc')->select()->toarray();
            foreach ($tmp as $k => $vo) {
                $tmp[$k]['childs'] = MenuModel::where('parent_uuid',$vo['uuid'])->where('uuid','in',$role['menu_uuids'])->select()->toarray();
            }
            $role['menus'] = $tmp;
            $data['role'] = $role;
        }
        return $data;
    }


    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new AdminModel();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['uuid'] = uuidCreate();
            $save_data['password'] = md5($save_data['password']);
            if ( $model->where('account',$save_data['account'])->count() > 0 ) {
                throw new \Exception("账号已存在");
            }
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
    public function updateData($uuid,$save_data=[]){
        $model = new AdminModel();
        // 启动事务 
        Db::startTrans();
        try{
            if (!empty($save_data)) {
                if (!empty($save_data['password'])) {
                    $save_data['password'] = md5($save_data['password']);
                }
                if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
                    throw new \Exception("保存失败");
                }
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
     * 删除
     * @Author   cch
     * @DateTime 2020-06-10T15:19:31+0800
     * @param    [type]                   $uuid [description]
     * @return   [type]                         [description]
     */
    public function delete($uuid){
        $model = new AdminModel();
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



}
