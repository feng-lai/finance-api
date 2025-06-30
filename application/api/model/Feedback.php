<?php 
namespace app\api\model;
use think\Model;

class Feedback extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getLocationCn($key){
		$list = ['家长端','机构端','老师端','班主任端'];
		return $list[$key];
	}

	public static function getTypeCn($key){
		$list = ['学生问题','教师问题','平台问题'];
		return $list[$key];
	}	

	public static function getStatusCn($key){
		$list = ['未读','已读'];
		return $list[$key];
	}	
}


 ?>