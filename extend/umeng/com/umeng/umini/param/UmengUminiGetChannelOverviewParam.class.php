<?php

include_once ('../extend/umeng/com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/entity/ByteArray.class.php');

class UmengUminiGetChannelOverviewParam {

        
        /**
    * @return 开始时间
    */
        public function getFromDate() {
        $tempResult = $this->sdkStdResult["fromDate"];
        return $tempResult;
    }
    
    /**
     * 设置开始时间     
     * @param String $fromDate     
     * 参数示例：<pre>2020-03-10</pre>     
     * 此参数必填     */
    public function setFromDate( $fromDate) {
        $this->sdkStdResult["fromDate"] = $fromDate;
    }
    
        
        /**
    * @return 结束时间
    */
        public function getToDate() {
        $tempResult = $this->sdkStdResult["toDate"];
        return $tempResult;
    }
    
    /**
     * 设置结束时间     
     * @param String $toDate     
     * 参数示例：<pre>2020-03-31</pre>     
     * 此参数必填     */
    public function setToDate( $toDate) {
        $this->sdkStdResult["toDate"] = $toDate;
    }
    
        
        /**
    * @return 渠道
    */
        public function getChannel() {
        $tempResult = $this->sdkStdResult["channel"];
        return $tempResult;
    }
    
    /**
     * 设置渠道     
     * @param String $channel     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setChannel( $channel) {
        $this->sdkStdResult["channel"] = $channel;
    }
    
        
        /**
    * @return 时间颗粒度(可选时间颗粒度：day,7day,30day)
    */
        public function getTimeUnit() {
        $tempResult = $this->sdkStdResult["timeUnit"];
        return $tempResult;
    }
    
    /**
     * 设置时间颗粒度(可选时间颗粒度：day,7day,30day)     
     * @param String $timeUnit     
     * 参数示例：<pre>day</pre>     
     * 此参数必填     */
    public function setTimeUnit( $timeUnit) {
        $this->sdkStdResult["timeUnit"] = $timeUnit;
    }
    
        
        /**
    * @return 数据源id
    */
        public function getDataSourceId() {
        $tempResult = $this->sdkStdResult["dataSourceId"];
        return $tempResult;
    }
    
    /**
     * 设置数据源id     
     * @param String $dataSourceId     
     * 参数示例：<pre></pre>     
     * 此参数必填     */
    public function setDataSourceId( $dataSourceId) {
        $this->sdkStdResult["dataSourceId"] = $dataSourceId;
    }
    
        
        /**
    * @return 多个指标时，以逗号分隔（newUser,activeUser,launch,visitTimes,onceDuration）
    */
        public function getIndicators() {
        $tempResult = $this->sdkStdResult["indicators"];
        return $tempResult;
    }
    
    /**
     * 设置多个指标时，以逗号分隔（newUser,activeUser,launch,visitTimes,onceDuration）     
     * @param String $indicators     
     * 参数示例：<pre>activeUser</pre>     
     * 此参数必填     */
    public function setIndicators( $indicators) {
        $this->sdkStdResult["indicators"] = $indicators;
    }
    
        
        /**
    * @return 页码
    */
        public function getPageIndex() {
        $tempResult = $this->sdkStdResult["pageIndex"];
        return $tempResult;
    }
    
    /**
     * 设置页码     
     * @param Integer $pageIndex     
     * 参数示例：<pre>1</pre>     
     * 此参数为可选参数
     
     * 默认值：<pre>1</pre>
          */
    public function setPageIndex( $pageIndex) {
        $this->sdkStdResult["pageIndex"] = $pageIndex;
    }
    
        
        /**
    * @return 每页记录数
    */
        public function getPageSize() {
        $tempResult = $this->sdkStdResult["pageSize"];
        return $tempResult;
    }
    
    /**
     * 设置每页记录数     
     * @param Integer $pageSize     
     * 参数示例：<pre>30</pre>     
     * 此参数为可选参数
     
     * 默认值：<pre>30</pre>
          */
    public function setPageSize( $pageSize) {
        $this->sdkStdResult["pageSize"] = $pageSize;
    }
    
        
    private $sdkStdResult=array();
    
    public function getSdkStdResult(){
    	return $this->sdkStdResult;
    }

}
?>