<?php
namespace app\api\logic\cms;
use app\api\model\User as UserModel;
use app\api\model\Order as OrderModel;
use app\api\model\OrderRefund as OrderRefundModel;
use app\api\model\Institution as InstitutionModel;
use app\api\model\PublicAudit as PublicAuditModel;
use app\api\model\Course as CourseModel;

use think\Db;


class Statistics
{

    // 用户分析 - 头部统计
    public function userTotal(){
        // 获取今日
        $curtime = date('Y-m-d');
        $search_map = [];
        $search_map[] = ['create_time','between',[$curtime.' 0:0:0',$curtime.' 23:59:59']];
        // 家长数据
        $udata = [
            'total'=>UserModel::where('is_delete',0)->count(),
            'stage_1'=>UserModel::where([
                ['is_delete','=',0],
                ['stage','>',0]
            ])->count(),
            'td_num'=>UserModel::where($search_map)->count(),
        ];
        // 机构数据
        $idata = [
            'total'=>InstitutionModel::count(),
            'stage_1'=>InstitutionModel::where([
                ['stage','>',0]
            ])->count(),
            'td_num'=>InstitutionModel::where($search_map)->count(),
        ];
        $data = [
            'total'=>$udata['total']+$idata['total'],
            'stage_1'=>$udata['stage_1']+$idata['stage_1'],
            'td_num'=>$udata['td_num']+$idata['td_num']
        ];
        $data['refund_num'] = OrderRefundModel::where($search_map)->group('user_uuid')->count();
        return $data;
    }

    // 用户分析 - 趋势图
    public function userTrend($params,$map=[]){
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $days = ceil(($end_time - $start_time) / 86400);

        $list = [];
        for ($i=0; $i <= $days; $i++) { 
            $tmp = [
                'date'=>date('m-d',$start_time),
                'user'=>0,
                'institution'=>0
            ];
            $start_time = date('Y-m-d',$start_time);

            $tmp_map = $map;
            if ($params['type'] == 0) {
                $tmp_map[] = ['create_time','<',$start_time.' 23:59:59'];
                $tmp['user'] = UserModel::where($tmp_map)->count();
                $tmp['institution'] = InstitutionModel::where($tmp_map)->count();
            }elseif ($params['type'] == 1) {
                $tmp_map[] = ['create_time','between',[$start_time.' 0:0:0',$start_time.' 23:59:59']];
                $tmp_map2 = $tmp_map;
                $tmp_map2[] = ['user_type','=',0];
                $tmp['user'] = OrderModel::where($tmp_map2)->group('user_uuid')->count();
                $tmp_map2 = $tmp_map;
                $tmp_map2[] = ['user_type','=',1];
                $tmp['institution'] = OrderModel::where($tmp_map2)->group('user_uuid')->count();
            }elseif ($params['type'] == 2) {
                $tmp_map[] = ['create_time','between',[$start_time.' 0:0:0',$start_time.' 23:59:59']];
                $tmp['user'] = UserModel::where($tmp_map)->count();
                $tmp['institution'] = InstitutionModel::where($tmp_map)->count();
            }elseif ($params['type'] == 3) {
                $tmp_map[] = ['create_time','between',[$start_time.' 0:0:0',$start_time.' 23:59:59']];
                $tmp_map2 = $tmp_map;
                $tmp_map2[] = ['user_type','=',0];
                $tmp['user'] = OrderRefundModel::where($tmp_map2)->group('user_uuid')->count();
                $tmp_map2 = $tmp_map;
                $tmp_map2[] = ['user_type','=',1];
                $tmp['institution'] = OrderRefundModel::where($tmp_map2)->group('user_uuid')->count();
            }
            
            $list[] = $tmp;

            $start_time = strtotime($start_time) + 86400;
        }
        return $list;
    }

    // 用户分析 - 阶段数据
    public function userStage(){
        $data = [];
        $data['user'] = [
            'stage_0'=>UserModel::where('stage',0)->count(),
            'stage_1'=>UserModel::where('stage',1)->count(),
            'stage_2'=>UserModel::where('stage',2)->count(),
            'refund'=>OrderRefundModel::where('user_type',0)->group('user_uuid')->count()
        ];
        $data['institution'] = [
            'stage_0'=>InstitutionModel::where('stage',0)->count(),
            'stage_1'=>InstitutionModel::where('stage',1)->count(),
            'stage_2'=>InstitutionModel::where('stage',2)->count(),
            'refund'=>OrderRefundModel::where('user_type',1)->group('user_uuid')->count()
        ];
        return $data;
    }

    // 财务统计 - 头部统计
    public function financeTotal(){
        // 获取今日
        $curtime = date('Y-m-d');
        $search_map = [];
        $search_map[] = ['create_time','between',[$curtime.' 0:0:0',$curtime.' 23:59:59']];

        $data = [
            'total_price'=>OrderModel::where('status','not in',[0,-1])->sum('total_price'),
            'total_num'=>OrderModel::count()
        ];
        
        $search_map2 = $search_map;
        $search_map2[] = ['status','not in',[0,-1]];
        $data['td_price'] = OrderModel::where($search_map2)->sum('total_price');

        $search_map2 = $search_map;
        $search_map2[] = ['status','not in',[-1]];
        $data['td_refund_num'] = OrderRefundModel::where($search_map2)->sum('fee');

        return $data;
    }

    // 财务数据分析
    public function financeTrend($params,$map=[]){
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $days = ceil(($end_time - $start_time) / 86400);

        $list = [];
        for ($i=0; $i <= $days; $i++) { 
            $tmp = ['date'=>date('m-d',$start_time)];
            $start_time = date('Y-m-d',$start_time);

            $tmp_map = $map;
            $tmp_map[] = ['create_time','between',[$start_time.' 0:0:0',$start_time.' 23:59:59']];
            $tmp_map[] = ['status','not in',[0,-1]];
            $tmp['price'] = OrderModel::where($tmp_map)->sum('total_price');
            $list[] = $tmp;

            $start_time = strtotime($start_time) + 86400;
        }
        return $list;
    }

    // 财务数据分析
    public function financeList($params,$map=[]){
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $days = ceil(($end_time - $start_time) / 86400);

        $list = [];
        for ($i=0; $i <= $days; $i++) { 
            $tmp = [
                'date'=>date('m-d',$start_time),
                'total_price'=>0,
                'public_audit'=>0,
                'course_0'=>0,
                'course_1'=>0,
                'course_2'=>0,
                'course_3'=>0,
                'refund_price'=>0
            ];
            $start_time = date('Y-m-d',$start_time);

            $tmp_map = $map;
            $tmp_map[] = ['create_time','between',[$start_time.' 0:0:0',$start_time.' 23:59:59']];
            $tmp_map[] = ['status','not in',[0,-1]];
            $data = OrderModel::field('course_type,sum(total_price) as total_price')->where($tmp_map)->group('course_type')->select()->toarray();
            foreach ($data as $vo) {
                $tmp['course_'.$vo['course_type']] = $vo['total_price'];
            }
            $tmp['total_price'] = OrderModel::where($tmp_map)->sum('total_price');

            $tmp_map2 = $tmp_map;
            $tmp_map2[] = ['user_type','=',0];
            $tmp['course_0_0'] = OrderModel::where($tmp_map2)->sum('total_price');
            $tmp['course_0_1'] = $tmp['course_0'] - $tmp['course_0_0'];

            $tmp_map[] = ['pay_type','=',3];
            $data['public_audit'] = OrderModel::where($tmp_map)->sum('total_price');

            $tmp_map = $map;
            $tmp_map[] = ['create_time','between',[$start_time.' 0:0:0',$start_time.' 23:59:59']];
            $tmp_map[] = ['status','not in',[-1]];
            $data['refund_price'] = OrderRefundModel::where($tmp_map)->sum('fee');



            $list[] = $tmp;

            $start_time = strtotime($start_time) + 86400;
        }
        return $list;
    }

    /**
     * 导出Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:05:51+0800
     * @return   excel下载地址
     */
    public function financeExport($params,$map=[]){
        $list = $this->financeList($params,$map);

        $data = [];
        $data[] = ['时间', '总收入', '对公收入','家长体验课收入','机构体验课收入','私教课收入','小班课收入','大班课收入','退款'];
        foreach ($list as $k => $vo) {
            $tmp = [
                $vo['date'],$vo['total_price'],$vo['public_audit'],$vo['course_0_0'],$vo['course_0_1'],
                $vo['course_1'],$vo['course_3'],$vo['course_2'],$vo['refund_price']
            ];
            foreach ($tmp as $tmp_k => $tmp_v) {
                $tmp[$tmp_k] = $tmp_v.'';
            }
            $data[] = $tmp;
        }

        try{
            $excel = new \PHPExcel();
            $excel_sheet = $excel->getActiveSheet();
            $excel_sheet->fromArray($data);
            $excel_writer = \PHPExcel_IOFactory::createWriter($excel,'Excel2007');
            
            $file_name = '交易分析.xlsx';
            $file_path = './excel/'.$file_name;
            $excel_writer->save($file_path);
            if (!file_exists($file_path)) {
                throw new \Exception("Excel生成失败");
            }
            $result = uploadFileExcel($file_name,$file_path,'hld_education/excel/');
            return $result;
        } catch (\Exception $e) {
            // 更新失败 回滚事务
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    // 课程分析 - 头部统计
    public function courseTotal(){
        $data = [
            'course_0'=>0,
            'course_1'=>0,
            'course_2'=>0,
            'course_3'=>0
        ];
        $map[] = ['status','not in',[0,-1]];
        $tmp = OrderModel::field('course_type,sum(lesson_num) as lesson_num')->where($map)->group('course_type')->select()->toarray();
        foreach ($tmp as $vo) {
            $data['course_'.$vo['course_type']] = $vo['lesson_num'];
        }
        return $data;
    }

    // 财务数据分析
    public function courseTrend($params,$map=[]){
        $start_time = strtotime($params['start_time']);
        $end_time = strtotime($params['end_time']);
        $days = ceil(($end_time - $start_time) / 86400);

        $list = [];
        for ($i=0; $i <= $days; $i++) { 
            $tmp = ['date'=>date('m-d',$start_time)];
            $start_time = date('Y-m-d',$start_time);

            $tmp_map = $map;
            $tmp_map[] = ['create_time','between',[$start_time.' 0:0:0',$start_time.' 23:59:59']];
            $tmp_map[] = ['status','not in',[0,-1]];
            $tmp_map[] = ['course_type','=',$params['course_type']];
            $tmp['lesson_num'] = OrderModel::where($tmp_map)->sum('lesson_num');
            $list[] = $tmp;

            $start_time = strtotime($start_time) + 86400;
        }
        return $list;
    }

    // 财务数据分析
    public function courseList($params,$map=[]){
        // $start_time = strtotime($params['start_time']);
        // $end_time = strtotime($params['end_time']);
        // $days = ceil(($end_time - $start_time) / 86400);
        $map[] = ['status','not in',[0,-1]];
        $map[] = ['course_type','=',$params['course_type']];

        $list = OrderModel::field('course_uuid,count(*) as num')->where($map)->group('course_uuid')->order('num desc')->limit(10)->select()->toarray();
        foreach ($list as $k => $vo) {
            $list[$k]['course_name'] = CourseModel::where('uuid',$vo['course_uuid'])->value('name');
        }
        return $list;
    }

    
}
