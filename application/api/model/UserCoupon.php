<?php 
namespace app\api\model;
use think\Model;

class UserCoupon extends Model
{	
	protected $autoWriteTimestamp = 'datetime';

	public static function getTypeCn($key){
		$list = ['邀请活动','新人活动','普通会员活动','VIP会员赠送','平台发放'];
		return $list[$key];
	}	
}


 ?>