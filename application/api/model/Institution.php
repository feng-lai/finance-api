<?php 
namespace app\api\model;
use think\Model;

class Institution extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getTypeCn($key){
		$list = ['普通','付费'];
		return $list[$key];
	}

	public static function getStatusCn($key){
		$list = ['封禁','正常'];
		return $list[$key];
	}	
	
}


 ?>