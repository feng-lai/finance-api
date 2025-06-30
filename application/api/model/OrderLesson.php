<?php 
namespace app\api\model;
use think\Model;

class OrderLesson extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getInitCn($key){
		$list = ['未排课','已排课'];
		return $list[$key];
	}
}


 ?>