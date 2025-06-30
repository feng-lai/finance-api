<?php 
namespace app\api\model;
use think\Model;
use think\Db;

class Order extends Model
{	
	protected $autoWriteTimestamp = 'datetime';

	public static function getPayTypeCn($key){
		$list = ['未知','支付宝','微信','对公'];
		return $list[$key];
	}	

	public static function getStatusCn($key){
		$list = ['待支付','待开始','上课中','已完成',-1=>'已取消'];
		return $list[$key];
	}

	public static function getDeliveryCom($key=''){
		$list = [
			'yuantong'=>'圆通速递',
			'yunda'=>'韵达快递',
			'shunfeng'=>'顺丰速运',
			'zhongtong'=>'中通快递',
			'youzhengguonei'=>'邮政快递包裹',
			'shentong'=>'申通快递',
			'jd'=>'京东物流',
			'ems'=>'EMS'
		];
		if (!empty($key)) {
			foreach ($list as $k => $vo) {
				if ($vo == $key) {
					$key = $k;
					break;
				}
			}
			return $key;
		}else{
			return $list;
		}
	}	

	public function statisticsCourse_3($course_uuid){
		$course = Db::name('course')->where('uuid',$course_uuid)->find();
		if ($course['type'] == 3) {
			$params = json_decode($course['params'],true);
			$order_num = Db::name('order')->where([
	            ['course_uuid','=',$course['uuid']],
	            ['status','>',0]
	        ])->count();
	        if ($order_num >= $params['class_num']) {
	        	Db::name('order')->where([
	                ['course_uuid','=',$course['uuid']],
	                ['status','=',1]
	            ])->update(['is_class'=>1]);
	        	
	        	// 将其他未支付的，该课程订单取消
	        	Db::name('order')->where([
	                ['course_uuid','=',$course['uuid']],
	                ['status','=',0]
	            ])->update(['status'=>-1]);
	        }
		}
	}

	// public static function getRefunds($uuid){
	// 	$data = Db::name('order')->where('uuid',$uuid)->find();
	// 	$refunds = [];
	// 	$tmp = [
	// 		'sn'=>$data['order_sn'],'status'=>0,'type'=>0,'create_time'=>date('Y-m-d H:i:s'),
	// 		'order_uuid'=>$data['uuid'],'user_uuid'=>$data['user_uuid']
	// 	];
	// 	if ($data['status'] == 2) {
	// 		$tmp['total_fee'] = $tmp['fee'] = $data['total_price'];
	// 	}else{
	// 		$tmp['fee'] = $data['appraisal_price'] - $data['discount_price'];
	// 		$tmp['total_fee'] = $data['appraisal_price'] - $data['discount_price'] + $data['delivery_price'];
	// 	}
	// 	$refunds[] = $tmp;
 //        if ($data['difference_price'] > 0) {
 //            $refunds[] = [
 //            	'sn'=>$data['difference_sn'],'status'=>0,'type'=>1,'create_time'=>date('Y-m-d H:i:s'),
	// 			'order_uuid'=>$data['uuid'],'user_uuid'=>$data['user_uuid'],
	// 			'fee'=>$data['difference_price'],'total_fee'=>$data['difference_price']
 //            ];
 //        }
 //        return $refunds;
	// }
	
}


 ?>