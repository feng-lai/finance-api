<?php 
namespace app\api\model;
use think\Model;

class AdminLogs extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static $actions = ['save'=>'新增','update'=>'编辑','updateOne'=>'编辑','delete'=>'删除','cancel'=>'编辑'];
	public static function getActionCn($key){
		$list = self::$actions;
		return $list[$key];
	}

	public static function getControllerCn($key){
		$list = [
			'Admin'=>'管理员',
			'AppraisalCase'=>'案例',
			'Banner'=>'轮播图',
			'Category'=>'类目',
			'CategoryBrand'=>'品牌',
			'CategoryPart'=>'部分',
			'CategoryUpload'=>'上传要求',
			'Config'=>'系统参数',
			'Coupon'=>'优惠券',
			'Gemmologist'=>'鉴定师',
			'HotCity'=>'热门城市',
			'InsideMsg'=>'站内消息',
			'Inspector'=>'检验员',
			'Institution'=>'机构',
			'Menu'=>'菜单',
			'Notice'=>'公告',
			'Order'=>'订单',
			'OrderCertificate'=>'证书',
			'OrderInvoice'=>'发票',
			'OrderQr'=>'订单二维码',
			'OrderRefund'=>'退款',
			'OutsideMsg'=>'站外消息',
			'PriceSection'=>'价格区间',
			'Question'=>'试题',
			'Questionnaire'=>'问卷调查',
			'Receiver'=>'接收员',
			'Role'=>'角色',
			'Store'=>'商家',
			'StoreBill'=>'商家流水',
			'SystemBill'=>'系统流水',
			'User'=>'用户',
			'UserBill'=>'用户流水',
			'UserCoupon'=>'用户优惠券',
		];
		return $list[$key];
	}
}


 ?>