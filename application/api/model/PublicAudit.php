<?php 
namespace app\api\model;
use think\Model;

class PublicAudit extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getStatusCn($key){
		$list = ['待处理','通过',-1=>'拒绝'];
		return $list[$key];
	}
}


 ?>