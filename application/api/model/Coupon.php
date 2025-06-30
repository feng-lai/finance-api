<?php 
namespace app\api\model;
use think\Model;

class Coupon extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getTypeCn($key){
		$list = ['折扣券','代金券'];
		return $list[$key];
	}	

	public static function getActivityCn($key){
		$list = ['邀请活动',' 新人活动','其他'];
		return $list[$key];
	}	

	public static function getOrderTypeCn($key){
		$list = ['图片',' 实物',-1=>'不限'];
		return $list[$key];
	}	
}


 ?>