<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Index as IndexLogic;


class Index extends Base
{
    public function index()
    {
        $data = array('test'=>1,'test2'=>2);
        return json($data);
    }


    public function hello($user,$name){
    	$indexLogic = new IndexLogic();
    	$data = $indexLogic->index();
    	dump($data);

        // 抛出异常
        throw new Exeption('异常信息',10086);
        abort('404','页面不存在');
    }


}
