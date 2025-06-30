<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// Route::get('think', function () {
//     return 'hello,ThinkPHP5!';
// });

use think\facade\Route;
use app\api\model\Order;
use app\api\model\OrderRenew;
use app\api\model\Signs;

//订单状态变更
Route::get('order_status_change', function (){
    //活动中 已完成
    $info = Order::field('uuid,order_time')->order('create_time desc')->json(['order_time'])->where('status','in',[2,3])->select();
    foreach($info as $v){
        if(strtotime($v->order_time[0]->from) <= time() && time() <= strtotime(end($v->order_time)->to)){
            //活动中
            Order::where('uuid',$v->uuid)->update(['status'=>3]);
        }
        if(time() >= strtotime(end($v->order_time)->to)){
            //已完成
            Order::where('uuid',$v->uuid)->update(['status'=>4]);
        }


    }
});

//通知明天活动
Route::get('activity_notify', function (){
    $map = [
        ['s.status','=',1],
        ['s.notify','=',0]
    ];
    $map[] = ['a.start_time','>=',date('Y-m-d',strtotime('+1 day'))];
    $map[] = ['a.start_time','<=',date('Y-m-d',strtotime('+2 day'))];
    $info = Signs::field('u.openid,a.start_time,s.name,a.name,a.address,s.uuid')
        ->alias('s')
        ->leftJoin('activity a','a.uuid = s.activity_uuid')
        ->leftJoin('user u','u.uuid = s.user_uuid')
        ->where($map)
        ->select();
    foreach ($info as $v) {
        $app = app('wechat.mini_program');
        $data = [
            'template_id' => 'cxvd7bXiIecQRGlzTdthhj3d6Q2PSI8phlQnnBDZzo0', // 所需下发的订阅模板id
            'touser' => $v->openid,     // 接收者（用户）的 openid
            //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
            'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                'thing4' => [
                    'value' => $v->name, //活动名称
                ],
                'date3' => [
                    'value' => $v->start_time, //开始时间
                ],
                'thing6' => [
                    'value' => $v->address, //地点
                ],
                'thing7' => [
                    'value' => '活动将于明天开始', //提示
                ],
            ],
        ];
        $app->subscribe_message->send($data);
        Signs::where('uuid',$v->uuid)->update(['notify'=>1]);
    }
});

//通知明天会议开始
Route::get('order_notify', function (){
    $map = [
        ['o.status','=',2],
        ['o.notify','=',0]
    ];
    $info = Order::field('u.openid,o.order_time,o.name,p.address,o.uuid')
        ->json(['order_time'])
        ->alias('o')
        ->leftJoin('places p','p.uuid = o.places_uuid')
        ->leftJoin('user u','u.uuid = o.user_uuid')
        ->where($map)
        ->select();
    foreach ($info as $v) {
        if($v->order_time[0]->from >= date('Y-m-d 00:00',strtotime('+1 day')) && $v->order_time[0]->from <= date('Y-m-d 24:00',strtotime('+1 day'))){
            $app = app('wechat.mini_program');
            $data = [
                'template_id' => 'LzcQOMWRQog3G8fhV6JCzJs7Acrm2p4gCEi6BzV6dz4', // 所需下发的订阅模板id
                'touser' => $v->openid,     // 接收者（用户）的 openid
                //'page' => '',       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
                'data' => [         // 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
                    'name1' => [
                        'value' => $v->name, //预约人
                    ],
                    'thing2' => [
                        'value' => $v->address, //地点
                    ],
                    'time22' => [
                        'value' => $v->order_time[0]->from, //开始时间
                    ],
                    'time23' => [
                        'value' => end($v->order_time)->to, //结束时间
                    ],
                    'thing7' => [
                        'value' => '您预约的场地将于明天开始,请提前做好准备', //备注
                    ],
                ],
            ];
            $app->subscribe_message->send($data);
            Order::where('uuid',$v->uuid)->update(['notify'=>1]);
        }
    }
});

Route::group(":version", function () {

    Route::group("cms", function () {
        Route::rule('UploadFile', 'api/:version.cms.Base/uploadFile');

        Route::group("Admin", function () {
            Route::get(':uuid', 'api/:version.cms.Admin/read');
            Route::get('', 'api/:version.cms.Admin/index');
            Route::post('', 'api/:version.cms.Admin/save');
            Route::put('', 'api/:version.cms.Admin/update');
            Route::delete(':uuid', 'api/:version.cms.Admin/delete');
        });
        Route::group("AdminCaptcha", function () {
            Route::get('', 'api/:version.cms.Admin/getCaptcha');
        });
        Route::group("AdminLogin", function () {
            Route::get('', 'api/:version.cms.Admin/getLoginInfo');
            Route::post('', 'api/:version.cms.Admin/login');
        });
        Route::group("AdminLogout", function () {
            Route::get('', 'api/:version.cms.Admin/logout');
        });

        Route::group("Menu", function () {
            Route::get(':uuid', 'api/:version.cms.Menu/read');
            Route::get('', 'api/:version.cms.Menu/index');
            Route::post('', 'api/:version.cms.Menu/save');
            Route::put('', 'api/:version.cms.Menu/update');
            Route::delete(':uuid', 'api/:version.cms.Menu/delete');
            Route::post('many', 'api/:version.cms.Menu/many');
        });

        Route::group("Role", function () {
            Route::get(':uuid', 'api/:version.cms.Role/read');
            Route::get('', 'api/:version.cms.Role/index');
            Route::post('', 'api/:version.cms.Role/save');
            Route::put('', 'api/:version.cms.Role/update');
            Route::delete(':uuid', 'api/:version.cms.Role/delete');
        });

        Route::get('AdminLogs', 'api/:version.cms.AdminLogs/index');

        Route::group("Config", function () {
            Route::get('', 'api/:version.cms.Config/read');
            Route::put('', 'api/:version.cms.Config/update');
        });
        Route::group("ConfigMore", function () {
            Route::get('', 'api/:version.cms.Config/moreRead');
            Route::put('', 'api/:version.cms.Config/moreUpdate');
        });

        Route::group("User", function () {
            Route::get(':uuid', 'api/:version.cms.User/read');
            Route::get('', 'api/:version.cms.User/index');
            // Route::post('','api/:version.cms.User/save');
            Route::put('', 'api/:version.cms.User/update');
            Route::delete(':uuid', 'api/:version.cms.User/delete');
        });
        Route::group("UserExport", function () {
            Route::get('', 'api/:version.cms.User/export');
        });
        Route::group("UserOlStatistics", function () {
            Route::get('', 'api/:version.cms.User/olStatistics');
        });

        Route::group("Banner", function () {
            Route::get(':uuid', 'api/:version.cms.Banner/read');
            Route::get('', 'api/:version.cms.Banner/index');
            Route::post('', 'api/:version.cms.Banner/save');
            Route::put('', 'api/:version.cms.Banner/update');
            Route::delete(':uuid', 'api/:version.cms.Banner/delete');
        });


        Route::group("Activity", function () {
            Route::get(':uuid', 'api/:version.cms.Activity/read');
            Route::get('', 'api/:version.cms.Activity/index');
            Route::post('', 'api/:version.cms.Activity/save');
            Route::put('', 'api/:version.cms.Activity/update');
            Route::delete(':uuid', 'api/:version.cms.Activity/delete');
        });




        Route::group("OrderRefund", function () {
            Route::get(':uuid', 'api/:version.cms.Order_Refund/read');
            Route::get('', 'api/:version.cms.Order_Refund/index');
            Route::put('', 'api/:version.cms.Order_Refund/update');
            // Route::delete(':uuid','api/:version.cms.Order_Refund/delete');
        });
        Route::group("OrderRefundExport", function () {
            Route::get('', 'api/:version.cms.Order_Refund/export');
        });



        Route::group("Order", function () {
            Route::get(':uuid', 'api/:version.cms.Order/read');
            Route::get('', 'api/:version.cms.Order/index');
            Route::post('', 'api/:version.cms.Order/save');
            Route::put('', 'api/:version.cms.Order/update');
            Route::delete(':uuid', 'api/:version.cms.Order/delete');
        });

        //月结订单审核
        Route::put('OrderVerify', 'api/:version.cms.Order/verify');

        Route::group("OrderCancel", function () {
            Route::post('', 'api/:version.cms.Order/cancel');
        });
        Route::group("OrderPay", function () {
            Route::post('', 'api/:version.cms.Order/pay');
        });
        Route::group("OrderExport", function () {
            Route::get('', 'api/:version.cms.Order/export');
        });

        Route::group("StatisticsUserTotal", function () {
            Route::get('', 'api/:version.cms.Statistics/userTotal');
        });
        Route::group("StatisticsUserTrend", function () {
            Route::get('', 'api/:version.cms.Statistics/userTrend');
        });
        Route::group("StatisticsUserStage", function () {
            Route::get('', 'api/:version.cms.Statistics/userStage');
        });
        Route::group("StatisticsFinanceTotal", function () {
            Route::get('', 'api/:version.cms.Statistics/financeTotal');
        });
        Route::group("StatisticsFinanceTrend", function () {
            Route::get('', 'api/:version.cms.Statistics/financeTrend');
        });
        Route::group("StatisticsFinanceList", function () {
            Route::get('', 'api/:version.cms.Statistics/financeList');
        });
        Route::group("StatisticsFinanceExport", function () {
            Route::get('', 'api/:version.cms.Statistics/financeExport');
        });
        Route::group("StatisticsCourseTotal", function () {
            Route::get('', 'api/:version.cms.Statistics/courseTotal');
        });
        Route::group("StatisticsCourseTrend", function () {
            Route::get('', 'api/:version.cms.Statistics/courseTrend');
        });
        Route::group("StatisticsCourseList", function () {
            Route::get('', 'api/:version.cms.Statistics/courseList');
        });


        Route::group("SystemBill", function () {
            Route::get(':uuid', 'api/:version.cms.System_Bill/read');
            Route::get('', 'api/:version.cms.System_Bill/index');
        });

        Route::group("SystemBillTotal", function () {
            Route::get('', 'api/:version.cms.System_Bill/statistics');
        });

        Route::group("SystemBillExport", function () {
            Route::get('', 'api/:version.cms.System_Bill/export');
        });


        //动态播报管理
        Route::group("Articles", function () {
            Route::get(':uuid', 'api/:version.cms.Articles/read');
            Route::get('', 'api/:version.cms.Articles/index');
            Route::post('', 'api/:version.cms.Articles/save');
            Route::put('', 'api/:version.cms.Articles/update');
            Route::delete(':uuid', 'api/:version.cms.Articles/delete');
        });


        //月结编号
        Route::group("PayNumbers", function () {
            Route::get(':uuid', 'api/:version.cms.PayNumbers/read');
            Route::get('', 'api/:version.cms.PayNumbers/index');
            Route::post('', 'api/:version.cms.PayNumbers/save');
            Route::put('', 'api/:version.cms.PayNumbers/update');
            Route::delete(':uuid', 'api/:version.cms.PayNumbers/delete');
        });

        //场地管理
        Route::group("Places", function () {
            Route::get(':uuid', 'api/:version.cms.Places/read');
            Route::get('', 'api/:version.cms.Places/index');
            Route::post('', 'api/:version.cms.Places/save');
            Route::put('', 'api/:version.cms.Places/update');
            Route::delete(':uuid', 'api/:version.cms.Places/delete');
            Route::put('lock', 'api/:version.cms.Places/lock');
        });

        //发票管理
        Route::group("Invoice", function () {
            Route::get(':uuid', 'api/:version.cms.Invoice/read');
            Route::get('', 'api/:version.cms.Invoice/index');
            Route::post('', 'api/:version.cms.Invoice/save');
            Route::put('', 'api/:version.cms.Invoice/update');
            Route::delete(':uuid', 'api/:version.cms.Invoice/delete');
        });

        //发票导出
        Route::get('InvoiceExport', 'api/:version.cms.Invoice/export');

        //发票抬头管理
        Route::group("InvoiceTitle", function () {
            Route::get(':uuid', 'api/:version.cms.InvoiceTitle/read');
            Route::get('', 'api/:version.cms.InvoiceTitle/index');
            Route::post('', 'api/:version.cms.InvoiceTitle/save');
            Route::put('', 'api/:version.cms.InvoiceTitle/update');
            Route::delete(':uuid', 'api/:version.cms.InvoiceTitle/delete');
        });

        //报名管理
        Route::group("Signs", function () {
            Route::get(':uuid', 'api/:version.cms.Signs/read');
            Route::get('', 'api/:version.cms.Signs/index');
            Route::post('', 'api/:version.cms.Signs/save');
            Route::put('', 'api/:version.cms.Signs/update');
            Route::delete(':uuid', 'api/:version.cms.Signs/delete');
        });

        //报名导出
        Route::get('SignsExport', 'api/:version.cms.Signs/export');

        //批量更新
        Route::group("SignsBatch", function () {
            Route::put('', 'api/:version.cms.Signs/batch');
        });


        Route::group("Notify", function () {
            Route::get('', function (){
                //$page_param = ['page'=>input('page_index',1),'list_rows'=>input('page_size',10)];
                if(input('status')){
                    return jsonResult(['status'=>1,'msg'=>'成功','data'=>\app\api\model\Notify::field('create_time,content,uuid,status')->order('create_time desc')->where('status',input('status'))->select()]);
                }else{
                    return jsonResult(['status'=>1,'msg'=>'成功','data'=>\app\api\model\Notify::field('create_time,content,uuid,status')->order('create_time desc')->select()]);
                }

            });
            Route::put(':uuid', function (){
                checkInputEmptyByExit(['uuid']);
                \app\api\model\Notify::where('uuid',input('uuid'))->update(['status'=>1]);
                return jsonResult(['status'=>1,'msg'=>'成功']);
            });
            Route::delete(':uuid', function (){
                checkInputEmptyByExit(['uuid']);
                \app\api\model\Notify::where('uuid',input('uuid'))->delete();
                return jsonResult(['status'=>1,'msg'=>'成功']);
            });
        });

    });
    Route::group("mini", function () {
        Route::rule('UploadFile', 'api/:version.cms.Base/uploadFile');
        //动态播报管理
        Route::group("Articles", function () {
            Route::get(':uuid', 'api/:version.mini.Articles/read');
            Route::get('', 'api/:version.mini.Articles/index');
        });

        //场地管理
        Route::group("Places", function () {
            Route::get(':uuid', 'api/:version.mini.Places/read');
            Route::get('', 'api/:version.mini.Places/index');
        });

        //订单(场地预定)
        Route::group("Order", function () {
            Route::get(':uuid', 'api/:version.mini.Order/read');
            Route::get('', 'api/:version.mini.Order/index');
            Route::post('', 'api/:version.mini.Order/save');
            Route::put('', 'api/:version.mini.Order/update');
        });

        //取消
        Route::post('OrderCancel', 'api/:version.mini.Order/cancel');

        //订单续订
        Route::post('OrderRenew', 'api/:version.mini.Order/renew');

        //支付
        Route::group("Pay", function () {
            Route::post('', 'api/:version.mini.Pay/pay');
            Route::post('Renew', 'api/:version.mini.Pay/renew');
            Route::any('reback', 'api/:version.mini.Pay/reback');
            Route::any('renewReback', 'api/:version.mini.Pay/renew_reback');
        });

        //活动管理
        Route::group("Activity", function () {
            Route::get(':uuid', 'api/:version.mini.Activity/read');
            Route::get('', 'api/:version.mini.Activity/index');
        });

        //活动管理
        Route::group("Signs", function () {
            Route::get('', 'api/:version.mini.Signs/index');
            Route::get(':uuid', 'api/:version.mini.Signs/read');
            Route::post('', 'api/:version.mini.Signs/save');
            Route::put('', 'api/:version.mini.Signs/update');
        });


        //用户
        Route::group("User", function () {
            Route::post('login', 'api/:version.mini.User/login');
            Route::get('', 'api/:version.mini.User/read');
            Route::put('', 'api/:version.mini.User/update');
        });

        Route::get('UserMobile', 'api/:version.mini.User/mobile');

        Route::get('Banner', function (){
            return jsonResult(['status'=>1,'msg'=>'成功','data'=>\app\api\model\Banner::field('url,image')->where('status',1)->select()]);
        });

        Route::group("Config", function () {
            Route::get('', 'api/:version.mini.Config/read');
        });

        Route::group("ConfigMore", function () {
            Route::get('', 'api/:version.mini.Config/moreRead');
        });

        //发票管理
        Route::group("Invoice", function () {
            Route::get(':uuid', 'api/:version.mini.Invoice/read');
            Route::get('', 'api/:version.mini.Invoice/index');
            Route::post('', 'api/:version.mini.Invoice/save');
            Route::put('', 'api/:version.mini.Invoice/update');
            Route::delete(':uuid', 'api/:version.mini.Invoice/delete');
        });

        //发票抬头管理
        Route::group("InvoiceTitle", function () {
            Route::get(':uuid', 'api/:version.mini.InvoiceTitle/read');
            Route::get('', 'api/:version.mini.InvoiceTitle/index');
            Route::post('', 'api/:version.mini.InvoiceTitle/save');
            Route::put('', 'api/:version.mini.InvoiceTitle/update');
            Route::delete(':uuid', 'api/:version.mini.InvoiceTitle/delete');
        });

        //退订
        Route::group("OrderRefund", function () {
            Route::get(':uuid', 'api/:version.mini.Order_Refund/read');
            Route::get('', 'api/:version.mini.Order_Refund/index');
            Route::put('', 'api/:version.mini.Order_Refund/update');
            Route::post('', 'api/:version.mini.Order_Refund/save');
            // Route::delete(':uuid','api/:version.cms.Order_Refund/delete');
        });

        //微信退款通知
        Route::any('OrderRefund_reback', 'api/:version.mini.Order_Refund/reback');

        //微信查询退款
        Route::get('OrderRefund_check', 'api/:version.mini.Order_Refund/check');

        //订单退订申请查询能退的钱
        Route::get('OrderRefund_price/:uuid', 'api/:version.mini.Order_Refund/price');

        Route::get('PayNumbers', function (){
            if(input('description')){
                $data = \app\api\model\PayNumbers::where('description','like','%'.input('description').'%')->select();
            }else{
                $data = \app\api\model\PayNumbers::select();
            }
            return jsonResult(['status'=>1,'msg'=>'成功','data'=>$data]);
        });
    });

});

return [

];

