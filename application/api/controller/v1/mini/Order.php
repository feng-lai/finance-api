<?php
namespace app\api\controller\v1\mini;
use app\api\controller\v1\mini\Base;
use app\api\logic\mini\Order as OrderLogic;
use think\Db;

class Order extends Base
{
    /**
     * @Author   CCH
     * @DateTime 2020-05-23T12:10:10+0800
     * @return   数据列表
     */
    public function index(){
        $map_params = [
            ['key'=>'status','type'=>'='],
            ['key'=>'pay_type','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $map[] = ['name|order_sn','=',$keyword_search];
        }
        if (isSearchParam('create_time')) {
            $map[] = ['create_time','>=',date('Y-m-d',strtotime(input('create_time')))];
            $map[] = ['create_time','<=',date('Y-m-d',strtotime(input('create_time').'+1 day'))];
        }
        $map[] = ['status','<>',0];
        $map[] = ['user_uuid','=',input('user_uuid')];
        $logic = new OrderLogic();
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
        $logic = new OrderLogic();
        $data = $logic->read($uuid);
        return $this->apiResult('2000','获取成功',$data);
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
            'must'=>['places_uuid','pay_type','name','phone','company','services','num','welcome_word','nameplate','order_time'],
            'nomust'=>['others']
        ];
        if(input('pay_type') == 2){
            if(!input('pay_number')){
                return $this->apiResult('5000','月结编号不能为空');
            }
        }
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }
        
        $save_data['user_uuid'] = input('user_uuid');
        $save_data['user_type'] = 0;

        $logic = new OrderLogic();
        $result = $logic->saveData($save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','提交成功',$result['uuid']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    //改签
    public function update(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        $fields = [
            'must'=>['order_time','uuid']
        ];

        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        $logic = new OrderLogic();
        $result = $logic->update($save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','提交成功',$result['uuid']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    //续钟
    public function renew(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        $fields = [
            'must'=>['renew_time','order_uuid','pay_type']
        ];

        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        if(input('pay_type') == 2){
            if(!input('pay_number')){
                return $this->apiResult('5000','月结编号不能为空');
            }
            $save_data['pay_number'] = input('pay_number');
        }

        $logic = new OrderLogic();
        $result = $logic->renew($save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','提交成功',$result['uuid']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }



    /**
     * 取消订单
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function cancel(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        if (empty($param['uuid'])) {
            exception('uuid不能为空',400);
        }

        $logic = new OrderLogic();
        $result = $logic->cancel($param['uuid']);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','取消成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }
    
    /**
     * 删除
     * @Author   CCH
     * @DateTime 2020-05-30T15:27:04+0800
     * @return   [type]                   [description]
     */
    public function delete($uuid){
        $logic = new OrderLogic();
        $result = $logic->delete($uuid);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','删除成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 完成订单
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function finish(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        if (empty($param['uuid'])) {
            exception('uuid不能为空',400);
        }

        $fields = [
            'nomust'=>[]
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        $logic = new OrderLogic();
        $result = $logic->finish($param['uuid'],$save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 发票申请
     * @Author   cch
     */
    public function invoiceApply(){
        $user_uuid = input('user_uuid');
        if (empty($user_uuid)) {
            exception('用户UUID不能为空',400);
        }

        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        $fields = [
            'must'=>['order_uuid','title','name'],
            'nomust'=>['invoice_image','type']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }
        $save_data['user_uuid'] = $user_uuid;

        if ($save_data['title'] == 1) {
            $invoice_fields = [
                'must'=>['ti_number','address','mobile','bank_name','bank_account'],
            ];
            $invoice_data = paramFilter($param,$invoice_fields);
            if (!empty($invoice_data['error_msg'])) {
                exception($invoice_data['error_msg'],400);
            }
            $save_data = array_merge($save_data,$invoice_data);
        }

        $logic = new OrderLogic();
        $result = $logic->invoiceApply($save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    /**
     * 完成订单
     * @Author   cch
     * @DateTime 2020-05-26T17:22:32+0800
     * @return   [type]                   [description]
     */
    public function pay(){
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);
        if (empty($param['uuid'])) {
            exception('uuid不能为空',400);
        }

        $fields = [
            'nomust'=>[]
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }

        $logic = new OrderLogic();
        $result = $logic->pay($param['uuid'],$save_data);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }



}
