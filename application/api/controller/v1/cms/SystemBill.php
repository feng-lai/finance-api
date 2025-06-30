<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\SystemBill as SystemBillLogic;
use think\Db;

class SystemBill extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   头部统计
     */
    public function statistics(){
        $map_params = [
            
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('start_time')) {
            $map[] = ['create_time','>=',input('start_time')];
        }
        if (isSearchParam('end_time')) {
            $map[] = ['create_time','<=',input('end_time')];
        }
        $logic = new SystemBillLogic();
        $list = $logic->statistics($map);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $map_params = [
            ['key'=>'type','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('start_time')) {
            $map[] = ['s.create_time','>=',input('start_time')];
        }
        if (isSearchParam('end_time')) {
            $map[] = ['s.create_time','<=',input('end_time').' 24:00:00'];
        }
        if (isSearchParam('keyword_search')) {
            $map[] = ['u.nickname|u.mobile|o.order_sn','like','%'.input('keyword_search').'%'];
        }
        if (isSearchParam('pay_type')) {
            $map[] = ['o.pay_type','=',input('pay_type')];
        }
        if (isSearchParam('pay_number')) {
            $map[] = ['o.pay_number','=',input('pay_number')];
        }
        $logic = new SystemBillLogic();
        $list = $logic->getList($map);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-26T17:22:45+0800
     * @return   [type]                   [description]
     */
    public function read($uuid){
        $logic = new SystemBillLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
    }

    /**
     * 导出Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:04:48+0800
     * @return   excel下载地址
     */
    public function export(){
        $map_params = [
            ['key'=>'s.type','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('start_time')) {
            $map[] = ['s.create_time','>=',input('start_time')];
        }
        if (isSearchParam('end_time')) {
            $map[] = ['s.create_time','<=',input('end_time')];
        }
        if (isSearchParam('keyword_search')) {
            $map[] = ['u.nickname|u.mobile|o.order_sn','like','%'.input('keyword_search').'%'];
        }
        if (isSearchParam('pay_type')) {
            $map[] = ['o.pay_type','=',input('pay_type')];
        }
        if (isSearchParam('pay_number')) {
            $map[] = ['o.pay_number','=',input('pay_number')];
        }

        $logic = new SystemBillLogic();
        $result = $logic->exportExcel($map);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }
}
