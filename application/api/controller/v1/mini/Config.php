<?php
namespace app\api\controller\v1\mini;
use app\api\controller\v1\mini\Base;
use app\api\logic\cms\Config as ConfigLogic;

class Config extends Base
{
    protected $noCheckToken = ['read','moreRead'];
    /**
     * 获取系统参数
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   列表
     */
    public function read($key){
        $logic = new ConfigLogic();
        $list = $logic->read($key);
        return $this->apiResult('2000','获取成功',$list);
    }


    /**
     * 获取系统参数
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   列表
     */
    public function moreRead($key){
        $logic = new ConfigLogic();
        $list = $logic->moreRead($key);
        return $this->apiResult('2000','获取成功',$list);
    }


}
