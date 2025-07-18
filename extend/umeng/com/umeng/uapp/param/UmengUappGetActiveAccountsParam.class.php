<?php

include_once ('../extend/umeng/com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/entity/ByteArray.class.php');

class UmengUappGetActiveAccountsParam {

        
        /**
    * @return 应用ID
    */
        public function getAppkey() {
        $tempResult = $this->sdkStdResult["appkey"];
        return $tempResult;
    }
    
    /**
     * 设置应用ID     
     * @param String $appkey     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setAppkey( $appkey) {
        $this->sdkStdResult["appkey"] = $appkey;
    }
    
        
        /**
    * @return 查询起始日期（例如:2018-01-01）
    */
        public function getStartDate() {
        $tempResult = $this->sdkStdResult["startDate"];
        return $tempResult;
    }
    
    /**
     * 设置查询起始日期（例如:2018-01-01）     
     * @param String $startDate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setStartDate( $startDate) {
        $this->sdkStdResult["startDate"] = $startDate;
    }
    
        
        /**
    * @return 查询截止日期（例如:2018-01-01）
    */
        public function getEndDate() {
        $tempResult = $this->sdkStdResult["endDate"];
        return $tempResult;
    }
    
    /**
     * 设置查询截止日期（例如:2018-01-01）     
     * @param String $endDate     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setEndDate( $endDate) {
        $this->sdkStdResult["endDate"] = $endDate;
    }
    
        
        /**
    * @return 查询类型（按日daily,按周weekly,按月monthly 查询）
    */
        public function getPeriodType() {
        $tempResult = $this->sdkStdResult["periodType"];
        return $tempResult;
    }
    
    /**
     * 设置查询类型（按日daily,按周weekly,按月monthly 查询）     
     * @param String $periodType     
     * 参数示例：<pre></pre>     
     * 此参数为可选参数
     
     * 默认值：<pre>daily</pre>
          */
    public function setPeriodType( $periodType) {
        $this->sdkStdResult["periodType"] = $periodType;
    }
    
        
        /**
    * @return 渠道名称（仅限一个例如:App%20Store）
    */
        public function getChannel() {
        $tempResult = $this->sdkStdResult["channel"];
        return $tempResult;
    }
    
    /**
     * 设置渠道名称（仅限一个例如:App%20Store）     
     * @param String $channel     
     * 参数示例：<pre></pre>     
     * 此参数为可选参数
     
     * 默认值：<pre></pre>
          */
    public function setChannel( $channel) {
        $this->sdkStdResult["channel"] = $channel;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>