<?php

include_once ('../extend/umeng/com/alibaba/openapi/client/entity/SDKDomain.class.php');
include_once ('../extend/umeng/com/alibaba/openapi/client/entity/ByteArray.class.php');
include_once ('../extend/umeng/com/umeng/uapp/param/UmengUappVersionInfo.class.php');

class UmengUappGetVersionDataResult {

        	
    private $versionInfos;
    
        /**
    * @return 
    */
        public function getVersionInfos() {
        return $this->versionInfos;
    }
    
    /**
     * 设置     
     * @param array include @see UmengUappVersionInfo[] $versionInfos     
          
     * 此参数必填     */
    public function setVersionInfos(UmengUappVersionInfo $versionInfos) {
        $this->versionInfos = $versionInfos;
    }
    
    	
	private $stdResult;
	
	public function setStdResult($stdResult) {
		$this->stdResult = $stdResult;
					    			    			if (array_key_exists ( "versionInfos", $this->stdResult )) {
    			$versionInfosResult=$this->stdResult->{"versionInfos"};
    				$object = json_decode ( json_encode ( $versionInfosResult ), true );
					$this->versionInfos = array ();
					for($i = 0; $i < count ( $object ); $i ++) {
						$arrayobject = new ArrayObject ( $object [$i] );
						$UmengUappVersionInfoResult=new UmengUappVersionInfo();
						$UmengUappVersionInfoResult->setArrayResult($arrayobject );
						$this->versionInfos [$i] = $UmengUappVersionInfoResult;
					}
    			}
    			    		    		}
	
	private $arrayResult;
	public function setArrayResult($arrayResult) {
		$this->arrayResult = $arrayResult;
				    		    		if (array_key_exists ( "versionInfos", $this->arrayResult )) {
    		$versionInfosResult=$arrayResult['versionInfos'];
    			$this->versionInfos = new UmengUappVersionInfo();
    			$this->versionInfos->setStdResult ( $versionInfosResult);
    		}
    		    	    		}

}
?>