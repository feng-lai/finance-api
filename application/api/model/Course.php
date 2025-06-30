<?php 
namespace app\api\model;
use think\Model;

class Course extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getTypeCn($key){
		$list = ['试听课','私教课','大班课','小班课'];
		return $list[$key];
	}
	
}


 ?>