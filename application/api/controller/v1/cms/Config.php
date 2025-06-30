<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Config as ConfigLogic;

class Config extends Base
{
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
     * 更新系统参数
     * @Author   cch
     * @DateTime 2020-06-10T15:14:45+0800
     * @return   
     */
    public function update(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        if (empty($param['key'])) {
            exception('key不能为空',400);
        }

        if (empty($param['value'])) {
            exception('value不能为空',400);
        }

        $logic = new ConfigLogic();
        $result = $logic->updateData($param['key'],$param['value']);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','更新成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
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

    /**
     * 更新系统参数
     * @Author   cch
     * @DateTime 2020-06-10T15:14:45+0800
     * @return   
     */
    public function moreUpdate(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        $content = json_decode($param['content'],true);
        if (empty($content)) {
            exception('content不能为空',400);
        }

        $logic = new ConfigLogic();
        $result = $logic->moreUpdate($content);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','更新成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }


}
