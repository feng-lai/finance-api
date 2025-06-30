<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\AdminLogs as AdminLogsLogic;
use think\Db;

class AdminLogs extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   列表
     */
    public function index(){
        $map = [];
        // if (isSearchParam('keyword_search')) {
        //     $map[] = ['account','like','%'.input('keyword_search').'%'];
        // }
        if (isSearchParam('keyword_search')) {
            $search_type = input('search_type','account');
            $keyword_search = input('keyword_search');
            $map[] = [$search_type,'like','%'.$keyword_search.'%'];
        }
        if (isSearchParam('start_time')) {
            $map[] = ['create_time','>=',input('start_time')];
        }
        if (isSearchParam('end_time')) {
            $map[] = ['create_time','<=',input('end_time')];
        }

        $logic = new AdminLogsLogic();
        $list = $logic->getList($map);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:40+0800
     * @param    $uuid [description]
     * @param    $lat  [description]
     * @param    $lng  [description]
     * @return         [description]
     */
    public function read($uuid){
        $logic = new AdminLogsLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
    }

   
}
