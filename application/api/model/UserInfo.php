<?php 
namespace app\api\model;
use think\Model;

class UserInfo extends Model
{	
	protected $autoWriteTimestamp = 'datetime';
	
	public static function getKeyCn($type,$key){
		$list = [
			0=>['headimgurl'=>'头像','truename'=>'姓名','profile'=>'个人简介','mechanism'=>'机构','position'=>'职位','mobile'=>'电话'],
			1=>['mobile'=>'电话','wechat'=>'微信','email'=>'邮箱','company'=>'公司','address'=>'地址'],
			10=>['honor'=>'荣誉','certificate'=>'证书','information'=>'文章','image_video'=>'照片视频']
		];
		return $list[$type][$key];
	}

	public static function getKeyList(){
		$list = [
			0=>['headimgurl'=>'头像','truename'=>'姓名','profile'=>'个人简介','mechanism'=>'机构','position'=>'职位','mobile'=>'电话'],
			1=>['mobile'=>'电话','wechat'=>'微信','email'=>'邮箱','company'=>'公司','address'=>'地址'],
			10=>['honor'=>'荣誉','certificate'=>'证书','information'=>'文章','image_video'=>'照片视频']
		];
		return $list;
	}

	public static function getRoleCn($key){
		$list = ['游客','普通用户','经销商','设计师','网店店主'];
		return $list[$key];
	}
}


 ?>