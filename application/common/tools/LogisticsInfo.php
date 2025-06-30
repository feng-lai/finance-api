<?php
/**
 * Created by PhpStorm.
 * User: Airon
 * Date: 2016/11/17
 * Time: 17:21
 *
 */
namespace app\common\tools;

use think\Cache;
use think\Db;
use think\Exception;
use JPush\Client as JPushSdk;
class LogisticsInfo
{
    public function getLogisticsInfo($num,$com){
        try {
        $post_data = array();
        $post_data["customer"] = 'B5E1B9C59F1DED973BFF1287F87A9F8B';
        $key= 'YcMiECFo3380' ;

        $array['com']=$com;
        $array['num']=$num;

        $post_data["param"] = json_encode($array);
//            var_dump($post_data);die;
        $url='http://poll.kuaidi100.com/poll/query.do';
        $post_data["sign"] = md5($post_data["param"].$key.$post_data["customer"]);
        $post_data["sign"] = strtoupper($post_data["sign"]);
        $o="";
        foreach ($post_data as $k=>$v)
        {
            $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
        }
        $post_data=substr($o,0,-1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($ch);
        $data = str_replace("\"",'"',$result );
        $data = json_decode($data,true);
        if(!empty($data['status'])){
            if ($data['status'] == "200") {
                return $data['data'];
            } else {
                return false;
            }
        }else{
            return false;
        }

    } catch (\Exception $e) {


         $error['msg']="快递100接口调用失败";
         return $error;
        }


}
}