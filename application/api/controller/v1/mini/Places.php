<?php
namespace app\api\controller\v1\mini;
use app\api\controller\v1\mini\Base;
use app\api\logic\mini\Places as PlacesLogic;

class Places extends Base
{
    protected $noCheckToken = ['index','read'];
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $map = [];
        if (isSearchParam('keyword_search')) {
            $map[] = ['name','like','%'.input('keyword_search').'%'];
        }
        $logic = new PlacesLogic();
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
        $logic = new PlacesLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
    }


}
