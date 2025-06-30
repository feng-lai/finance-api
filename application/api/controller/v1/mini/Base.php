<?php
namespace app\api\controller\v1\mini;
use think\Controller;
use app\api\model\Token as TokenModel;
use app\api\model\User as UserModel;
use think\Db;

class Base extends Controller
{
    public function __construct(){
        parent::__construct();
        $this->noCheckToken[] = 'uploadFile';
        $action = $this->request->action();
        //$controller = explode('.', $this->request->controller())[2];
        // 验证token
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
        	$token = TokenModel::where('access_token',$access_token)->find();
        	if ( empty($token) ) {
        		exception('token无效',401);
        	}
        	if ( time() > strtotime($token['expiry_time']) ) {
        		exception('token已过期',401);
        	}

            $admin = UserModel::where('uuid',$token['user_uuid'])->find();

        	// 将UUID放到param中
        	$this->request->user_uuid = $admin['uuid'];
        }
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
