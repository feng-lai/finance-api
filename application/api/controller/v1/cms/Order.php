<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Order as OrderLogic;
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
            ['key'=>'pay_type','type'=>'='],
            ['key'=>'user_uuid','type'=>'=']
        ];
        $map = getSearchParam($map_params);
        if (isSearchParam('keyword_search')) {
            $map[] = ['name|order_sn','=',input('keyword_search')];
        }
        $map[] = ['status','<>',0];
        if (isSearchParam('create_time')) {
            $map[] = ['create_time','>=',date('Y-m-d',strtotime(input('create_time')))];
            $map[] = ['create_time','<=',date('Y-m-d',strtotime(input('create_time').'+1 day'))];
        }
        $logic = new OrderLogic();
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
        
        $logic = new OrderLogic();
        $result = $logic->exportExcel($map);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
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
        $user_uuid = input('user_uuid');
        if (empty($user_uuid)) {
            exception('用户UUID不能为空',400);
        }
        $param = file_get_contents('php://input');
        $param = json_decode($param,true);

        $fields = [
            'must'=>['course_uuid','user_uuid','total_price'],
            'nomust'=>['remark','expect_time','user_coupon_uuid']
        ];
        $save_data = paramFilter($param,$fields);
        if (!empty($save_data['error_msg'])) {
            exception($save_data['error_msg'],400);
        }
        
        $save_data['user_uuid'] = $user_uuid;
        $save_data['user_type'] = 0;

        $logic = new OrderLogic();
        $result = $logic->saveData($save_data);
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
     * 支付
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

    public function verify(){
        checkInputEmptyByExit(['status','uuid']);
        if(!in_array(input('status'),[2,5])){
            return $this->apiResult('5000','status只能是2或者5');
        }
        $logic = new OrderLogic();
        $result = $logic->verify();
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功');
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

}
