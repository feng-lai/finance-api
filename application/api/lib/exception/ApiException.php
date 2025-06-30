<?php 
namespace app\api\lib\exception;

use think\exception\Handle;
use Exception;
use InvalidArgumentException;

class ApiException extends Handle {

   public function render(Exception $e){ // 重写render方法
   	$code = $e->getCode();
   	$msg = $e->getMessage();
   	if (!empty($code)) {
   		switch ($code) {
   			case 400: $error = ['type'=>'Bad Request','reason'=>'parameter "xxx" missing.','code'=>$code]; break;
   			case 401: $error = ['type'=>'Unauthorized','reason'=>'parameter "X-Access-Token" invalid','code'=>$code]; break;
   			case 403: $error = ['type'=>'Forbidden','reason'=>'parameter "xxx" missing.','code'=>$code]; break;
   			case 404: $error = ['type'=>'Not Found','reason'=>'account was ban','code'=>$code]; break;
   			case 409: $error = ['type'=>'Conflict','reason'=>'data already exists','code'=>$code]; break;
   			case 412: $error = ['type'=>'Precondition Failed','reason'=>'parameter "X-Access-Token" missing','code'=>$code]; break;
   			case 423: $error = ['type'=>'Locked','reason'=>'resource was locked','code'=>$code]; break;
   			default:
   				$error = ['type'=>'Internal Server Error','reason'=>'java.lang.NullPointerException at cn.dankal.xx.xxx() in line xxx'];
   				$code = 500;
   			break;
   		}
   		$error['message'] = $msg;
   	}elseif ($e instanceof InvalidArgumentException) {
   		$code = 400;
   		$error = ['type'=>'Bad Request','reason'=>$msg,'message'=>'请求体不完整'];
   	}else{
       if (!empty($msg)) {
         return apiResult('5000',$msg);
       }
   		$code = 500;
   		$error = ['type'=>'Internal Server Error','reason'=>'java.lang.NullPointerException at cn.dankal.xx.xxx() in line xxx'];
   	}
   	return json($error)->code($code);
   }

}
