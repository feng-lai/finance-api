<?php
namespace app\api\logic\cms;
use app\api\model\InsideMsg as InsideMsgModel;
use app\api\model\UserInsideMsg as UserInsideMsgModel;
use app\api\model\User as UserModel;
use app\api\model\Admin as AdminModel;
use app\api\model\Institution as InstitutionModel;
use think\Db;


class InsideMsg
{

	/**
	 * 获取列表
	 * @Author   CCH
	 * @DateTime 2020-05-23T12:18:51+0800
	 * @return   结果列表
	 */
    public function getList($map=[]){
        $model = new InsideMsgModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time','desc')->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['admin'] = AdminModel::field('account,truename,mobile')->find();
        }
        // 将消息设为已读
        // if (!empty($list['data'])) {
        //     $uuids = array_column($list['data'], 'uuid');
        //     $model->where('uuid','in',$uuids)->update(['status'=>1]);
        // }
        return $list;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new InsideMsgModel();
        // 启动事务 
        Db::startTrans();
        try{
            $save_data['uuid'] = uuidCreate();
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
            }
            // 如果是已发送状态，直接发送
            if ($save_data['status'] == 1) {
                $uim_datas = [];
                if ($save_data['location'] == 0) {
                    $ulist = UserModel::where('is_delete',0)->select();
                }else{
                    $ulist = InstitutionModel::select();
                }

                foreach ($ulist as $vo) {
                    $uim_datas[] = [
                        'uuid'=>uuidCreate(),
                        'create_time'=>date('Y-m-d H:i:s'),
                        'inside_msg_uuid'=>$save_data['uuid'],
                        'user_uuid'=>$vo['uuid'],
                        'user_type'=>$save_data['location']
                    ];
                }

                if ( !UserInsideMsgModel::insertAll($uim_datas) ) {
                    throw new \Exception("保存失败");
                }
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
        $model = new InsideMsgModel();
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
        $model = new InsideMsgModel();
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($data)) {
            
        }
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
        $model = new InsideMsgModel();
        Db::startTrans();
        try{
            if ( !$model->where('uuid',$uuid)->update(['status'=>-1]) ) {
                throw new \Exception("删除失败");
            }
            UserInsideMsgModel::where('inside_msg_uuid',$uuid)->update(['is_delete'=>1]);
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
     * 消息名单
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function userInsideMsgs($map=[]){
        $model = new UserInsideMsgModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time','desc')->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['user'] = UserModel::field('nickname,truename,mobile')->where('uuid',$vo['user_uuid'])->find();
        }
        return $list;
    }
}
