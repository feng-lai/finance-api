<?php 
namespace app\api\model;
use think\Model;

class UserBill extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getTypeCn($key){
		$list = ['购买','退款'];
		return $list[$key];
	}

	public static function getPayTypeCn($key){
		$list = ['','货到付款','微信','支付宝'];
		return $list[$key];
	}
}


 ?>