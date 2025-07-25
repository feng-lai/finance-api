<?php

include_once ('../extend/umeng/com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('../extend/umeng/com/umeng/uapp/param/UmengUappDailyDataInfo.class.php');

class UmengUappGetTodayDataResult {

        	
    private $todayData;
    
        /**
    * @return 
    */
        public function getTodayData() {
        return $this->todayData;
    }
    
    /**
     * 设置     
     * @param UmengUappDailyDataInfo $todayData     
          
     * 此参数必填     */
    public function setTodayData(UmengUappDailyDataInfo $todayData) {
        $this->todayData = $todayData;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "todayData", $this->stdResult )) {
    				$todayDataResult=$this->stdResult->{"todayData"};
    				$this->todayData = new UmengUappDailyDataInfo();
    				$this->todayData->setStdResult ( $todayDataResult);
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "todayData", $this->arrayResult )) {
    		$todayDataResult=$arrayResult['todayData'];
    			    			$this->todayData = new UmengUappDailyDataInfo();
    			    			$this->todayData->setStdResult ( $todayDataResult);
    		}
    		    	    		}

}
?>