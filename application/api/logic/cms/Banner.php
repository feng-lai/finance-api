<?php
namespace app\api\logic\cms;
use app\api\model\Banner as BannerModel;
use think\Db;


class Banner
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new BannerModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time desc')->paginate($page_param)->toarray();
        return $list;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new BannerModel();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['uuid'] = uuidCreate();
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
        $model = new BannerModel();
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
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @return         [description]
     */
    public function read($uuid){
        $model = new BannerModel();
        $data = $model->where('uuid',$uuid)->find();
        return $data;
    }

    /**
     * 删除
     * @Author   cch
     * @DateTime 2020-06-10T15:19:51+0800
     * @param    [type]                   $uuid [description]
     * @return   [type]                         [description]
     */
    public function delete($uuid){
        $model = new BannerModel();
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
