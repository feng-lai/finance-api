<?php
namespace app\api\controller\v1\cms;
use think\Controller;
use app\api\model\AdminToken as AdminTokenModel;
use app\api\model\Limit as LimitModel;
use app\api\model\Admin as AdminModel;
use app\api\model\RoleLimit as RoleLimitModel;

use app\api\logic\cms\AdminLogs as AdminLogsLogic;
use app\api\model\AdminLogs as AdminLogsModel;
// use think\Response;
use think\Db;

class Base extends Controller
{
    // appraisal/admin/5a5ea6ed9c14d4c619678573ea9b7166.png
    public function __construct(){
        parent::__construct();
        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE , OPTIONS');
        // header('Access-Control-Max-Age: 1728000');
        // if (strtoupper($this->request->method()) == "OPTIONS") {
        //     // return Response::create()->send();
        //     return json(['error'=>'200','message'=>'这是个Options请求'])->code(200);
        // }

        $action = $this->request->action();
        $controller = explode('.', $this->request->controller())[2];
        // 验证token
        $this->noCheckToken[] = 'uploadFile';
        if (!empty($this->noCheckToken)) {
            $noCheckToken = array_map('strtolower',$this->noCheckToken);
        }else{
            $noCheckToken = [];
        }
        if ( !in_array($action, $noCheckToken) ) {
        	if ( empty($this->request->header()['x-access-token']) ) {
        		exception('token不能为空',412);
        	}
        	$access_token = $this->request->header()['x-access-token'];
        	$token = AdminTokenModel::where('access_token',$access_token)->find();
        	if ( empty($token) ) {
        		exception('token无效',401);
        	}
        	if ( time() > strtotime($token['expiry_time']) ) {
        		exception('token已过期',401);
        	}

            $admin = AdminModel::where('uuid',$token['admin_uuid'])->find();
            // 验证权限
            // $limit_uuid = LimitModel::where(['controller'=>$controller,'action'=>$action])->value('uuid');
            // if (!empty($limit_uuid)) {
            //     $role_uuid = $admin['role_uuid'];
            //     if ( empty(RoleLimitModel::where(['role_uuid'=>$role_uuid,'limit_uuid'=>$limit_uuid])->value('id')) ) {
            //         exception('权限不足',401);
            //     }
            // }

        	// 将UUID放到param中
        	$this->request->login_admin_uuid = $admin['uuid'];
            // $this->request->login_admin_type = $admin['type'];
        }
        // $this->request->login_admin_uuid = '03887c288a6c774158280f93ed818ee5';
    }

    public function uploadFile(){
        $file = $_FILES['file'];
        if (empty($file)) {
            return $this->apiResult('5000','未检测到文件');
        }
        $result = uploadFile($file['name'],$file['tmp_name'],'xmh_motion/');
        if ($result['status'] == 1) {
            return $this->apiResult('2000','上传成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    // public function updateLimitNum(){
    //     // $list = Db::name('limit')->order('controller desc,is_top desc')->select();
    //     // foreach ($list as $k => $vo) {
    //     //     Db::name('limit')->where('name',$vo['name'])->update(['uuid'=>'ABC'.dispRepair($k+1,29)]);
    //     // }
    //     // 
    //     $list = Db::name('limit')->where('is_top',1)->select();
    //     foreach ($list as $k => $vo) {
    //         Db::name('limit')->where([['is_top','<>',1],['controller','=',$vo['controller']]])->update(['parent_uuid'=>$vo['uuid']]);
    //     }
    //     dump($list);die;
    // }
    
    // 统一返回值操作
    public function apiResult($code,$msg='',$data=null){
        // 保存操作日志
        if ($code == 2000) {
            $action = $this->request->action();
            $controller = explode('.', $this->request->controller())[2];

            $actions = AdminLogsModel::$actions;
            $actions = array_keys($actions);
            if (in_array($action, $actions)) {
                $save_data = [
                    'controller'=>$controller,
                    'action'=>$action,
                    'admin_uuid'=>input('login_admin_uuid')
                ];
                $logic = new AdminLogsLogic();
                $logic->saveAdminLogs($save_data);
            }
        }
        return apiResult($code,$msg,$data);
    }

    /**
     * 生成二维码
     * @Author   CCH
     * @DateTime 2020-06-07T17:25:21+0800
     * @param    $path  路径
     * @param    $width 宽度
     * @return   返回图片base数据给前端小程序，直接放前端img src即可显示
     */
    public function createQrCode($path,$width=150){
        $config = config('config.wxparam');
        $access_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$config['appid'].'&secret='.$config['secret'];
        $access_token = httpRequest( $access_token_url );
        $access_token = json_decode($access_token,true); 
        if (empty($access_token['access_token'])) {
            return apiResult('5000','access_token获取失败');
        }

        $qcode ="https://api.weixin.qq.com/wxa/getwxacode?access_token=".$access_token['access_token'];
        $param = json_encode(array("path"=>$path,"width"=> 150));
        $result = httpRequest( $qcode, $param , "POST");
        if (!empty($result)) {
            return apiResult('2000','二维码生成成功',"data:image/jpeg;base64,".base64_encode( $result ));
        }else{
            return apiResult('5000','二维码生成失败');
        }
    }

}
