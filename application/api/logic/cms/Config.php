<?php
namespace app\api\logic\cms;
use app\api\model\Config as ConfigModel;
use think\Db;


class Config
{
	/**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function read($key){
        $model = new ConfigModel();
        $value = $model->where('key',$key)->find();
        return $value;
    }

    /**
     * 更新
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function updateData($key,$value){
        $model = new ConfigModel();
        // 启动事务 
        Db::startTrans();
        try{
            if ( $model->where('key',$key)->update(['value'=>$value,'update_time'=>date('Y-m-d H:i:s')]) === false ) {
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
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function moreRead($key){
        $model = new ConfigModel();
        $key = explode(',', $key);
        $value = $model->where('key','in',$key)->select();
        return $value;
    }

    /**
     * 更新
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function moreUpdate($content){
        $model = new ConfigModel();
        // 启动事务 
        Db::startTrans();
        try{
            foreach ($content as $k => $vo) {
                if ( $model->where('key',$vo['key'])->update(['value'=>$vo['value'],'update_time'=>date('Y-m-d H:i:s')]) === false ) {
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

}
