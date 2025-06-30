<?php
namespace app\api\controller\v1\mini;
use app\api\controller\v1\mini\Base;
use app\api\logic\mini\Invoice as InvoiceLogic;


class Invoice extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $map[] = ['i.user_uuid','=',input('user_uuid')];
        if(input('status')){
            $map[] = ['i.status','=',input('status')];
        }
        $logic = new InvoiceLogic();
        $list = $logic->getList($map);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 保存
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function save(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        $fields = [
            'must'=>['invoice_title_uuid','content','order_uuid','type'],
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }
        $save_data['user_uuid'] = input('user_uuid');
        $save_data['apply_time'] = date('Y-m-d H:i:s',time());
        $logic = new InvoiceLogic();
        $result = $logic->saveData($save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','提交成功',$result['uuid']);
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
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        if (empty($param['uuid'])) {
            exception('uuid不能为空',400);
        }

        $fields = [
            'nomust'=>['content','amount','type'],
        ];
        $save_data = paramFilter($param,$fields);
        if (empty($save_data)) {
            return $this->apiResult('5000','无任何更改');
        }
        
        $logic = new InvoiceLogic();
        $result = $logic->updateData($param['uuid'],$save_data);
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
        $logic = new InvoiceLogic();
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
        $logic = new InvoiceLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
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
            ['key'=>'type','type'=>'='],
            ['key'=>'order_type','type'=>'='],
            ['key'=>'activity','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $map[] = ['name','like','%'.input('keyword_search').'%'];
        }
        $logic = new InvoiceLogic();
        $result = $logic->exportExcel($map);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 导入Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:04:48+0800
     * @return   excel下载地址
     */
    public function import(){
        $logic = new InvoiceLogic();
        $result = $logic->importExcel();
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

}
