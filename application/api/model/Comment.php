<?php 
namespace app\api\model;
use think\Model;

class Comment extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getStatusCn($key){
		$list = ['屏蔽','正常'];
		return $list[$key];
	}	
}


 ?>