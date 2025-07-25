<?php

include_once ('../extend/umeng/com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/entity/ByteArray.class.php');

class UmengUminiGetShareUserListParam {

        
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
    * @return 时间颗粒度（可选参数：day,7day,30day）
    */
        public function getTimeUnit() {
        $tempResult = $this->sdkStdResult["timeUnit"];
        return $tempResult;
    }
    
    /**
     * 设置时间颗粒度（可选参数：day,7day,30day）     
     * @param String $timeUnit     
     * 参数示例：<pre>day</pre>     
     * 此参数必填     */
    public function setTimeUnit( $timeUnit) {
        $this->sdkStdResult["timeUnit"] = $timeUnit;
    }
    
        
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
     * 参数示例：<pre>2020-08-10</pre>     
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
     * 参数示例：<pre>2020-08-10</pre>     
     * 此参数必填     */
    public function setToDate( $toDate) {
        $this->sdkStdResult["toDate"] = $toDate;
    }
    
        
        /**
    * @return 排序指标(count：分享次数；reflow：分享回流量；newUser：分享新增)
    */
        public function getOrderBy() {
        $tempResult = $this->sdkStdResult["orderBy"];
        return $tempResult;
    }
    
    /**
     * 设置排序指标(count：分享次数；reflow：分享回流量；newUser：分享新增)     
     * @param String $orderBy     
     * 参数示例：<pre></pre>     
     * 此参数为可选参数
     
     * 默认值：<pre>count</pre>
          */
    public function setOrderBy( $orderBy) {
        $this->sdkStdResult["orderBy"] = $orderBy;
    }
    
        
        /**
    * @return 排序方向（desc：降序,asc：正序）
    */
        public function getDirection() {
        $tempResult = $this->sdkStdResult["direction"];
        return $tempResult;
    }
    
    /**
     * 设置排序方向（desc：降序,asc：正序）     
     * @param String $direction     
     * 参数示例：<pre>desc</pre>     
     * 此参数为可选参数
     
     * 默认值：<pre>desc</pre>
          */
    public function setDirection( $direction) {
        $this->sdkStdResult["direction"] = $direction;
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