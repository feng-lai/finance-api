<?php
namespace app\api\logic\cms;
use app\api\model\Menu as MenuModel;
use think\Db;


class Menu
{

    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    // public function getList($map=[],$page_param=[]){
    //     $model = new MenuModel();
    //     $map[] = ['level','=',1];
    //     if ( !empty($page_param) ) {
    //         $list = $model->where($map)->order('create_time','asc')->paginate($page_param)->toarray();
    //         foreach ($list['data'] as $k => $vo) {
    //             $list['data'][$k]['childs'] = $model->where('parent_uuid',$vo['uuid'])->select();
    //         }  
    //     }else{
    //         $list = $model->where($map)->order('create_time','asc')->select();
    //         foreach ($list as $k => $vo) {
    //             $list[$k]['childs'] = $model->where('parent_uuid',$vo['uuid'])->select();
    //         }   
    //     }
    //     return $list;
    // }
    
    /**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new MenuModel();
        // $tmp = $model->where($map)->order('level asc,sort desc')->select()->toarray();
        // $list = [];
        // foreach ($tmp as $k => $vo) {
        //     if ($vo['level'] == 1) {
        //         $vo['childs'] = [];
        //         $list[$vo['uuid']] = $vo;
        //     }elseif ($vo['level'] == 2) {
        //         $vo['childs'] = [];
        //         !empty($list[$vo['parent_uuid']]) && $list[$vo['parent_uuid']]['childs'][$vo['uuid']] = $vo;
        //     }elseif ($vo['level'] == 3) {
        //         foreach ($list as $k2 => $v2) {
        //             if ( !empty($v2['childs'][$vo['parent_uuid']]) ) {
        //                 $list[$k2]['childs'][$vo['parent_uuid']]['childs'][] = $vo;
        //                 break;
        //             }
        //         }
        //     }
        // }
        // // 更新下标
        // $list = array_values($list);
        // foreach ($list as $k => $vo) {
        //     $list[$k]['childs'] = array_values($vo['childs']);
        // }
        //$list = $model->where($map)->order('level asc,sort desc')->select()->order('sort desc')->toarray();
        $list = $model->where($map)->order('sort asc')->select()->toarray();
        foreach ($list as $k => $vo) {
            $childs = $model->where('parent_uuid',$vo['uuid'])->order('sort asc')->select()->toarray();
            $list[$k]['childs'] = $childs;
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
        $model = new MenuModel();
        $data = $model->where('uuid',$uuid)->find();
        return $data;
    }


    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function saveData($save_data){
        $model = new MenuModel();
        if ($save_data['level'] == 1 && !empty($save_data['parent_uuid'])) {
            return ['status'=>0,'msg'=>'1级菜单不能有上级UUID'];
        }
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
        $model = new MenuModel();
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($save_data['parent_uuid']) && $data['uuid'] == $save_data['parent_uuid']) {
            return ['status'=>0,'msg'=>'上级UUID不能是自己'];
        }
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
    public function delete($uuid){
        $model = new MenuModel();
        Db::startTrans();
        try{
            if ( !$model->where('uuid',$uuid)->delete() ) {
                throw new \Exception("删除失败");
            }
            // 删除子菜单失败
            $model->where('parent_uuid',$uuid)->delete();
            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    public function many($data){
        foreach(json_decode($data,true) as $v){
            $model = new MenuModel();
            $uuid = uuidCreate();
            $save_data['uuid'] = $uuid;
            $save_data['name'] = $v['name'];
            $save_data['url'] = $v['url'];
            $save_data['sort'] = $v['sort'];
            $save_data['parent_uuid'] = '';
            if ( !$model->save($save_data) ) {
                throw new \Exception("保存失败");
            }
            if($v['child']){
                foreach($v['child'] as $val){
                    $model = new MenuModel();
                    $save_data['uuid'] = uuidCreate();
                    $save_data['name'] = $val['name'];
                    $save_data['url'] = $val['url'];
                    $save_data['sort'] = $val['sort'];
                    $save_data['parent_uuid'] = $uuid;
                    if ( !$model->save($save_data) ) {
                        throw new \Exception("保存失败");
                    }
                }
            }
        }
        return ['status'=>1];
    }

}
