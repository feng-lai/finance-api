<?php 
namespace app\api\model;
use think\Model;

class Teacher extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getStatusCn($key){
		$list = ['封禁','正常'];
		return $list[$key];
	}	
	
}


 ?>