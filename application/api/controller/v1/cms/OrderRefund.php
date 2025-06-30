<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\OrderRefund as OrderRefundLogic;
use think\Db;

class OrderRefund extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $logic = new OrderRefundLogic();
        $list = $logic->getList($map);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 导出Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:04:48+0800
     * @return   excel下载地址
     */
    public function export(){
        $map_params = [
            ['key'=>'status','type'=>'='],
            ['key'=>'is_refund','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $search_type = input('search_type','refund_sn');
            $keyword_search = input('keyword_search');
            if ($search_type == 'order_sn') {
                $uuids = Db::name('order')->where('order_sn','like','%'.$keyword_search.'%')->column('uuid');
                $map[] = ['order_uuid','in',$uuids];
            }elseif ($search_type == 'user_name') {
                $user_uuids = Db::name('user')->where('truename|nickname','like','%'.$keyword_search.'%')->column('uuid');
                $institution_uuids = Db::name('institution')->where('name','like','%'.$keyword_search.'%')->column('uuid');
                $uuids = array_merge((array)$user_uuids,(array)$institution_uuids);
                $map[] = ['user_uuid','in',$uuids];
            }elseif ($search_type == 'teacher_name') {
                $teacher_uuids = Db::name('teacher')->where('truename','like','%'.$keyword_search.'%')->column('uuid');
                $uuids = Db::name('order_teacher')->where('teacher_uuid','in',$teacher_uuids)->column('order_uuid');
                $map[] = ['order_uuid','in',$uuids];
            }elseif ($search_type == 'course_name') {
                $uuids = Db::name('course')->where('name','like','%'.$keyword_search.'%')->column('uuid');
                $map[] = ['course_uuid','in',$uuids];
            }else{
                $map[] = [$search_type,'like','%'.$keyword_search.'%'];
            }
        }
        if (isSearchParam('start_time')) {
            $map[] = ['create_time','>=',input('start_time')];
        }
        if (isSearchParam('end_time')) {
            $map[] = ['create_time','<=',input('end_time')];
        }
        $logic = new OrderRefundLogic();
        $result = $logic->exportExcel($map);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
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
        checkInputEmptyByExit(['uuid','status']);
        $logic = new OrderRefundLogic();
        $result = $logic->updateData(input('uuid'),input('status'));
        if ($result['status'] == 1) {
            return $this->apiResult('2000','更新成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-26T17:22:45+0800
     * @return   [type]                   [description]
     */
    public function read($uuid){
        $logic = new OrderRefundLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
    }

    /**
     * 删除
     * @Author   CCH
     * @DateTime 2020-05-30T15:27:04+0800
     * @return   [type]                   [description]
     */
    public function delete($uuid){
        $logic = new OrderRefundLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

}
