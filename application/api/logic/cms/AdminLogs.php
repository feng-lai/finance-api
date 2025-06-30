<?php
namespace app\api\logic\cms;
use app\api\model\AdminLogs as AdminLogsModel;
use app\api\model\Admin as AdminModel;
use app\api\model\Role as RoleModel;
use think\Db;


class AdminLogs
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new AdminLogsModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time','desc')->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['description'] = $vo['role_name'].'在 ['.$model::getControllerCn($vo['controller']).']进行了['.$model::getActionCn($vo['action']).']操作';
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
        $model = new AdminLogsModel();
        $data = $model->where('uuid',$uuid)->find();
        return $data;
    }


    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveAdminLogs($save_data){
        $model = new AdminLogsModel();
        $admin = AdminModel::where('uuid',$save_data['admin_uuid'])->find();
        $save_data['account'] = $admin['account'];
        $save_data['role_uuid'] = $admin['role_uuid'];
        $save_data['role_name'] = RoleModel::where('uuid',$save_data['role_uuid'])->value('name');

        // 启动事务 
        Db::startTrans();
        try{
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
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
     * 更新
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function updateAdminLogs($uuid,$save_data){
        $model = new AdminLogsModel();
        // 启动事务 
        Db::startTrans();
        try{
            if (!empty($save_data)) {
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
     * @DateTime 2020-06-10T15:20:58+0800
     * @param    [type]                   $uuid [description]
     * @return   [type]                         [description]
     */
    public function deleteAdminLogs($uuid){
        $model = new AdminLogsModel();
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
