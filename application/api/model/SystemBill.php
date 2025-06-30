<?php 
namespace app\api\model;
use think\Model;

class SystemBill extends Model
{	
	protected $autoWriteTimestamp = 'datetime';

	public static function getTypeCn($key){
		$list = ['收入','支出'];
		return $list[$key];
	}	

	public static function getBillTypeCn($key){
		$list = ['支付订单','对公收入','订单退款'];
		return $list[$key];
	}	
}


 ?>