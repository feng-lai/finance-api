<?php
namespace app\api\controller\v1\cms;
use app\api\controller\v1\cms\Base;
use app\api\logic\cms\Statistics as StatisticsLogic;

class Statistics extends Base
{


    // 用户分析 - 头部统计
    public function userTotal(){
        $logic = new StatisticsLogic();
        $data = $logic->userTotal();
        return $this->apiResult('2000','获取成功',$data);
    }

    // 用户分析 - 趋势图
    public function userTrend(){
        $map_params = [
        ];
        $map = getSearchParam($map_params);
        $fields = [
            'must'=>['start_time','end_time','type']
        ];
        $params = paramFilter(request()->param(),$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }
        $logic = new StatisticsLogic();
        $list = $logic->userTrend($params,$map);
        return $this->apiResult('2000','获取成功',$list);
    }

    // 用户分析 - 阶段数据
    public function userStage(){
        $logic = new StatisticsLogic();
        $data = $logic->userStage();
        return $this->apiResult('2000','获取成功',$data);
    }

    // 财务统计 - 头部统计
    public function financeTotal(){
        $logic = new StatisticsLogic();
        $list = $logic->financeTotal($search_time,$map);
        return $this->apiResult('2000','获取成功',$list);
    }

    // 财务数据分析
    public function financeTrend(){
        $map_params = [
        ];
        $map = getSearchParam($map_params);
        $fields = [
            'must'=>['start_time','end_time']
        ];
        $params = paramFilter(request()->param(),$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }
        $logic = new StatisticsLogic();
        $list = $logic->financeTrend($params,$map);
        return $this->apiResult('2000','获取成功',$list);
    }

    // 财务数据分析
    public function financeList(){
        $map_params = [
        ];
        $map = getSearchParam($map_params);
        $fields = [
            'must'=>['start_time','end_time']
        ];
        $params = paramFilter(request()->param(),$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }
        $logic = new StatisticsLogic();
        $list = $logic->financeList($params,$map);
        return $this->apiResult('2000','获取成功',$list);
    }

    /**
     * 导出Excel
     * @Author   cch
     * @DateTime 2020-06-05T15:04:48+0800
     * @return   excel下载地址
     */
    public function financeExport(){
        $map_params = [
        ];
        $map = getSearchParam($map_params);
        $fields = [
            'must'=>['start_time','end_time']
        ];
        $params = paramFilter(request()->param(),$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }
        $logic = new StatisticsLogic();
        $result = $logic->financeExport($params,$map);
        if ($result['status'] == 1) {
            return $this->apiResult('2000','操作成功',$result['data']);
        }else{
            return $this->apiResult('5000',$result['msg']);
        }
    }

    // 课程分析 - 头部统计
    public function courseTotal(){
        $logic = new StatisticsLogic();
        $list = $logic->courseTotal($search_time,$map);
        return $this->apiResult('2000','获取成功',$list);
    }

    // 财务数据分析
    public function courseTrend(){
        $map_params = [
        ];
        $map = getSearchParam($map_params);
        $fields = [
            'must'=>['start_time','end_time','course_type']
        ];
        $params = paramFilter(request()->param(),$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }
        $logic = new StatisticsLogic();
        $list = $logic->courseTrend($params,$map);
        return $this->apiResult('2000','获取成功',$list);
    }

    // 财务数据分析
    public function courseList(){
        $map_params = [
        ];
        $map = getSearchParam($map_params);
        $fields = [
            'must'=>['course_type'],
            'nomuset'=>['start_time','end_time']
        ];
        $params = paramFilter(request()->param(),$fields);
        if (!empty($params['error_msg'])) {
            exception($params['error_msg'],400);
        }
        $logic = new StatisticsLogic();
        $list = $logic->courseList($params,$map);
        return $this->apiResult('2000','获取成功',$list);
    }
    
}
