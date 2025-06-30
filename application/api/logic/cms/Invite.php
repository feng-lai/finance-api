<?php
namespace app\api\logic\cms;
use app\api\model\Invite as InviteModel;
use app\api\model\User as UserModel;
use think\Db;


class Invite
{
	/**
     * 获取列表
     * @Author   CCH
     * @DateTime 2020-05-23T12:18:51+0800
     * @return   结果列表
     */
    public function getList($map=[]){
        $model = new InviteModel();
        $page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
        $list = $model->where($map)->order('create_time','desc')->paginate($page_param)->toarray();
        foreach ($list['data'] as $k => $vo) {
            $list['data'][$k]['user'] = UserModel::field('uuid,nickname,mobile,truename,headimgurl')->where('uuid',$vo['user_uuid'])->find();
            $list['data'][$k]['coupon'] = json_decode($vo['coupon_data'],true);
            unset($list['data'][$k]['coupon_data']);
        }
        return $list;
    }

    /**
     * 获取详情
     * @Author   cch
     * @DateTime 2020-05-27T15:05:08+0800
     * @param    $uuid [description]
     * @return         [description]
     */
    public function read($uuid){
        $model = new InviteModel();
        $data = $model->where('uuid',$uuid)->find();
        if (!empty($data)) {
            $data['user'] = UserModel::field('uuid,nickname,mobile,truename,headimgurl')->where('uuid',$data['user_uuid'])->find();
            $data['coupon'] = json_decode($data['coupon_data'],true);
            unset($data['coupon_data']);
        }
        return $data;
    }

}
