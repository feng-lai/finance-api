<?php
namespace app\api\logic\cms;
use app\api\model\User as UserModel;
use app\api\model\Institution as InstitutionModel;
use app\api\model\Classes as ClassesModel;
use app\api\model\Headmaster as HeadmasterModel;

use app\api\model\Order as OrderModel;
use app\api\model\OrderLesson as OrderLessonModel;

use think\Db;

use PHPExcel_IOFactory;
use PHPExcel;


// ---------------------------引入接口参数类(以用户实际路径为准)---------------------------------

include_once ('../extend/umeng/com/umeng/uapp/param/UmengUappGetNewUsersParam.class.php');
include_once ('../extend/umeng/com/umeng/uapp/param/UmengUappGetNewUsersResult.class.php');

include_once ('../extend/umeng/com/umeng/uapp/param/UmengUappGetAllAppDataParam.class.php');
include_once ('../extend/umeng/com/umeng/uapp/param/UmengUappGetAllAppDataResult.class.php');

// ---------------------------引入SDK工具类(以用户实际路径为准)---------------------------------
include_once ('../extend/umeng/com/alibaba/openapi/client/policy/RequestPolicy.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/util/DateUtil.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/policy/ClientPolicy.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/APIRequest.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/APIId.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/SyncAPIClient.class.php');





class User
{

	/**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new UserModel();
        $map[] = ['is_delete','=',0];
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->field('nickname,headimgurl,mobile,create_time,uuid,remarks,company')->where($map)->order('create_time','desc')->paginate($page_param);
        return $list;
    }

    /**
     * 导出Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:05:51+0800
     * @return   excel下载地址
     */
    public function exportExcel($map=[]){
        request()->page_size = 99999999999;
        $list = $this->getList($map)->toArray();
        $list = $list['data'];

        $data = [];
        $data[] = ['id', '头像', '昵称','手机号','公司名称','注册时间'];
        foreach ($list as $k => $vo) {
            $tmp = [
                $k+1,$vo['headimgurl'],$vo['nickname'],$vo['mobile'],$vo['company'],$vo['create_time']
            ];

            foreach ($tmp as $tmp_k => $tmp_v) {
                $tmp[$tmp_k] = $tmp_v.'';
            }
            $data[] = $tmp;
        }
        try{
            $excel = new \PHPExcel();
            $excel_sheet = $excel->getActiveSheet();
            $excel_sheet->fromArray($data);
            $excel_writer = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
            
            $file_name = '用户数据.xlsx';
            $file_path = './excel/'.$file_name;
            $excel_writer->save($file_path);
            if (!file_exists($file_path)) {
                throw new \Exception("Excel生成失败");
            }
            $result = uploadFile($file_name,$file_path,'xmh_motion/');
            return $result;
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    public function olStatistics($map=[]){
        $model = new OrderLessonModel();
        $list = $model->field('course_type,count(*) as num')->where($map)->group('course_uuid')->select();
        $data = ['course_0'=>0,'course_1'=>0,'course_2'=>0,'course_3'=>0];
        foreach ($list as $k => $vo) {
            $data['course_'.$vo['course_type']] = $vo['num'];
        }
        $data['total'] = array_sum($data);
        return $data;
    }

    /**
     * 新增
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    // public function saveData($save_data){
    //     $model = new UserModel();
    //     // 启动事务 
    //     Db::startTrans();
    //     try{
    //         $save_data['uuid'] = uuidCreate();
    //         if ( !$model->save($save_data) ) {
    //             throw new \Exception("保存失败");
    //         }
    //         $user_infos = [];
    //         $uk_list = UserInfoModel::getKeyList();
    //         foreach ($uk_list as $ktype => $vo) {
    //             foreach ($vo as $k => $kname) {
    //                 $user_infos[] = [
    //                     'uuid'=>uuidCreate(),
    //                     'user_uuid'=>$save_data['uuid'],
    //                     'type'=>$ktype,
    //                     'key'=>$k,
    //                     'name'=>$kname,
    //                     'create_time'=>date('Y-m-d H:i:s')
    //                 ];
    //             }
    //         }
    //         if ( !UserInfoModel::insertAll($user_infos) ) {
    //             throw new \Exception("用户信息保存失败");
    //         }
    //         // 更新成功 提交事务
    //         Db::commit();
    //         return ['status'=>1,'uuid'=>$save_data['uuid']];
    //     } catch (\Exception $e) {
    //         // 更新失败 回滚事务
    //         Db::rollback();
    //         return ['status'=>0,'msg'=>$e->getMessage()];
    //     }
    // }

    /**
     * 更新
     * @Author   cch
     * @DateTime 2020-05-26T11:51:06+0800
     * @return   [type]
     */
    public function updateData($uuid,$save_data){
        $model = new UserModel();
        $data = $model->where('uuid',$uuid)->find();
        // 启动事务 
        Db::startTrans();
        try{
            if (!empty($save_data)) {
                if (!empty($save_data['mobile']) && $data['mobile'] != $save_data['mobile'] && $model->where('mobile',$save_data['mobile'])->count() > 0) {
                    throw new \Exception("手机号已存在");
                }
                if ( $model->where('uuid',$uuid)->update($save_data) === false ) {
                    throw new \Exception("保存失败");
                }
            }
            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    /**
     * 获取用户详情
     * @Author   cch
     * @DateTime 2020-05-26T17:24:19+0800
     * @param    $uuid 用户UUID
     * @return   [description]
     */
    public function read($uuid){
        $model = new UserModel();
        $data = $model->field('uuid,nickname,headimgurl,mobile,create_time,uuid,remarks,company')->where(['uuid'=>$uuid])->find();
        return $data;
    }

    /**
     * 删除
     * @Author   cch
     * @DateTime 2020-06-10T15:19:51+0800
     * @param    [type]                   $uuid [description]
     * @return   [type]                         [description]
     */
    public function delete($uuid){
        $model = new UserModel();
        Db::startTrans();
        try{
            if ( !$model->where('uuid',$uuid)->update([
                'is_delete'=>1,
                'mobile'=>null,
                'openid'=>null,
                'status'=>0
            ]) ) {
                throw new \Exception("删除失败");
            }
            // 更新成功 提交事务
            Db::commit();
            return ['status'=>1];
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            Db::rollback();
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    public function consumeRange($map=[]){
        $map[] = ['status','in',[1,2,3]];
        $model = new UserModel();
        // $list = $model->field('user.*,bb.total_price')->where([['is_delete','=',0]])
        // ->join('(select user_uuid,sum(total_price) as total_price from `order` where status in (1,2,3) group by user_uuid) as bb','bb.user_uuid = user.uuid','left')->select()->toarray();
        $list = Db::name('order')->field('user_uuid,sum(total_price) as total_price')->where($map)->group('user_uuid')->select();
        $user_num = $model->count();
        $result = [
            ['name'=>'未消费','num'=>$user_num-count($list),'ratio'=>0],
            ['name'=>'0~1000','num'=>0,'ratio'=>0],
            ['name'=>'1000~5000','num'=>0,'ratio'=>0],
            ['name'=>'5000~10000','num'=>0,'ratio'=>0],
            ['name'=>'10000+','num'=>0,'ratio'=>0]
        ];
        foreach ($list as $k => $vo) {
            if ($vo['total_price'] >= 10000) {
                $result[4]['num']++;
            }elseif($vo['total_price'] >= 5000){
                $result[3]['num']++;
            }elseif($vo['total_price'] >= 1000){
                $result[2]['num']++;
            }else{
                $result[1]['num']++;
            }
        }
        foreach ($result as $k => $vo) {
            $result[$k]['ratio'] = round($vo['num']/$user_num,2);
        }
        return $result;
    }


    public function userProfile($params=[]){
        // if ($params['platform'] == 'ios') {
        //     $app_key = '5fc9a5ef094d637f313484db';
        // }else{
        //     $app_key = '5fc9a615094d637f3134854a';
        // }

        try {
            // 请替换第一个参数apiKey和第二个参数apiSecurity
            $clientPolicy = new \ClientPolicy ('1250138', 'CjNoAGawuyOr', 'gateway.open.umeng.com');
            $syncAPIClient = new \SyncAPIClient ( $clientPolicy );

            $reqPolicy = new \RequestPolicy ();
            $reqPolicy->httpMethod = "POST";
            $reqPolicy->needAuthorization = false;
            $reqPolicy->requestSendTimestamp = false;
            // 测试环境只支持http
            // $reqPolicy->useHttps = false;
            $reqPolicy->useHttps = true;
            $reqPolicy->useSignture = true;
            $reqPolicy->accessPrivateApi = false;

            // --------------------------构造参数----------------------------------

            $param = new \UmengUappGetAllAppDataParam();

            // --------------------------构造请求----------------------------------

            $request = new \APIRequest ();
            $apiId = new \APIId ("com.umeng.uapp", "umeng.uapp.getAllAppData", 1 );
            $request->apiId = $apiId;
            $request->requestEntity = $param;

            // --------------------------构造结果----------------------------------
            $result = new \UmengUappGetAllAppDataResult();

            $syncAPIClient->send ( $request, $result, $reqPolicy );

            $result2 = $result->getAllAppData()[0];

            $data = [
                'td_activity_users'=>$result2->getTodayActivityUsers(),
                'td_new_users'=>$result2->getTodayNewUsers(),
                'td_launches'=>$result2->getTodayLaunches(),
                'yd_activity_users'=>$result2->getYesterdayActivityUsers(),
                'yd_new_users'=>$result2->getYesterdayNewUsers(),
                'yd_launches'=>$result2->getYesterdayLaunches(),
                'total_users'=>$result2->getTotalUsers()
            ];
            foreach ($data as $k => $vo) {
                if (empty($vo)) {
                    $data[$k] = 0;
                }
            }

            return ['status'=>1,'data'=>$data];
        } catch (\Exception $e) {
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    // public function object_array($array) {
    //     if(is_object($array)) {
    //         $array = (array)$array;
    //     }
    //     if(is_array($array)) {
    //         foreach($array as $key=>$value) {
    //             $array[$key] =$this->object_array($value);
    //         }
    //     }
    //     return $array;
    // }

    public function sexStatistics($map=[]){
        $map[] = ['status','in',[1,2,3]];
        $model = new UserModel();
        // $list = $model->field('user.*,bb.total_price')->where([['is_delete','=',0]])
        // ->join('(select user_uuid,sum(total_price) as total_price from `order` where status in (1,2,3) group by user_uuid) as bb','bb.user_uuid = user.uuid','left')->select()->toarray();
        $list = Db::name('user')->field('sex,count(*) as num')->where($map)->group('sex')->select();
        $result = ['sex_0'=>0,'sex_1'=>0,'sex_2'=>0];
        foreach ($list as $k => $vo) {
            $result['sex_'.$vo['sex']] = $vo['num'];
        }
        return $result;
    }

}
