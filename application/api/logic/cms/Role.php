<?php
namespace app\api\logic\cms;
use app\api\model\Role as RoleModel;
use app\api\model\RoleLimit as RoleLimitModel;
use app\api\model\Limit as LimitModel;
use app\api\model\Menu as MenuModel;
use think\Db;


class Role
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new RoleModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['menu_uuids'] = explode(',', $vo['menu_uuids']);
            $menu_names = MenuModel::where('uuid','in',$list['data'][$k]['menu_uuids'])->order('sort desc')->column('name');
            $list['data'][$k]['menu_names'] = $menu_names;
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
        $model = new RoleModel();
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($data)) {
            // $data['limits'] = RoleLimitModel::where('role_uuid',$data['uuid'])->column('limit_uuid');
            $data['menu_uuids'] = explode(',', $data['menu_uuids']);
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
        $model = new RoleModel();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['uuid'] = uuidCreate();
            $save_data['update_time'] = date('Y-m-d H:i:s');
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
        $model = new RoleModel();
        // 启动事务 
        Db::startTrans();
        try{
            if (!empty($save_data)) {
                $save_data['update_time'] = date('Y-m-d H:i:s');
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
     * @DateTime 2020-06-10T15:24:00+0800
     * @param    [type]                   $uuid [description]
     * @return   [type]                         [description]
     */
    public function delete($uuid){
        $model = new RoleModel();
        $nodel_role = config('config.nodel_role');
        if (in_array($uuid, $nodel_role)) {
            return ['status'=>0,'msg'=>'该角色不允许删除'];
        }
        Db::startTrans();
        try{
            if ( !$model->where('uuid',$uuid)->delete() ) {
                throw new \Exception("删除失败");
            }
            // 删除关联
            // RoleLimitModel::where('role_uuid',$uuid)->delete();
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
