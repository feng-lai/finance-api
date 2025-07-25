<?php namespace app\common\tools\core\src;

/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */
use Aliyun\Core\Auth\Credential;
use Aliyun\Core\Auth\ISigner;

abstract class RoaAcsRequest extends AcsRequest
{

    private static $headerSeparator = "\n";

    private static $querySeprator = "&";

    protected $uriPattern;

    protected $pathParameters = array();

    private $domainParameters = array();

    private $dateTimeFormat = 'D, d M Y H:i:s \G\M\T';


    public function  __construct($product, $version, $actionName)
    {
        parent::__construct($product, $version, $actionName);
        $this->setVersion($version);
        $this->initialize();
    }


    public function setVersion($version)
    {
        $this->version                  = $version;
        $this->headers["x-acs-version"] = $version;
    }


    private function initialize()
    {
        $this->setMethod("RAW");
    }


    public function composeUrl($iSigner, $credential, $domain)
    {
        $this->prepareHeader($iSigner);

        $signString = $this->getMethod() . self::$headerSeparator;
        if (isset( $this->headers["Accept"] )) {
            $signString = $signString . $this->headers["Accept"];
        }
        $signString = $signString . self::$headerSeparator;

        if (isset( $this->headers["Content-MD5"] )) {
            $signString = $signString . $this->headers["Content-MD5"];
        }
        $signString = $signString . self::$headerSeparator;

        if (isset( $this->headers["Content-Type"] )) {
            $signString = $signString . $this->headers["Content-Type"];
        }
        $signString = $signString . self::$headerSeparator;

        if (isset( $this->headers["Date"] )) {
            $signString = $signString . $this->headers["Date"];
        }
        $signString = $signString . self::$headerSeparator;

        $uri         = $this->replaceOccupiedParameters();
        $signString  = $signString . $this->buildCanonicalHeaders();
        $queryString = $this->buildQueryString($uri);
        $signString .= $queryString;
        /**
         * @var Credential $credential
         * @var ISigner    $iSigner
         */
        $this->headers["Authorization"] = "acs " . $credential->getAccessKeyId() . ":" . $iSigner->signString($signString, $credential->getAccessSecret());
        $requestUrl                     = $this->getProtocol() . "://" . $domain . $queryString;

        return $requestUrl;
    }


    private function prepareHeader($iSigner)
    {
        date_default_timezone_set("GMT");
        $this->headers["Date"] = date($this->dateTimeFormat);
        if (null == $this->acceptFormat) {
            $this->acceptFormat = "RAW";
        }
        /**
         * @var ISigner $iSigner
         */
        $this->headers["Accept"]                  = $this->formatToAccept($this->getAcceptFormat());
        $this->headers["x-acs-signature-method"]  = $iSigner->getSignatureMethod();
        $this->headers["x-acs-signature-version"] = $iSigner->getSignatureVersion();
    }


    private function formatToAccept($acceptFormat)
    {
        if ($acceptFormat == "JSON") {
            return "application/json";
        } elseif ($acceptFormat == "XML") {
            return "application/xml";
        }

        return "application/octet-stream";
    }


    private function replaceOccupiedParameters()
    {
        $result = $this->uriPattern;
        foreach ($this->pathParameters as $pathParameterKey => $apiParameterValue) {
            $target = "[" . $pathParameterKey . "]";
            $result = str_replace($target, $apiParameterValue, $result);
        }

        return $result;
    }


    private function buildCanonicalHeaders()
    {
        $sortMap = array();
        foreach ($this->headers as $headerKey => $headerValue) {
            $key = strtolower($headerKey);
            if (strpos($key, "x-acs-") === 0) {
                $sortMap[$key] = $headerValue;
            }
        }
        ksort($sortMap);
        $headerString = "";
        foreach ($sortMap as $sortMapKey => $sortMapValue) {
            $headerString = $headerString . $sortMapKey . ":" . $sortMapValue . self::$headerSeparator;
        }

        return $headerString;
    }


    private function buildQueryString($uri)
    {
        $uriParts = $this->splitSubResource($uri);
        $sortMap  = $this->queryParameters;
        if (isset( $uriParts[1] )) {
            $sortMap[$uriParts[1]] = null;
        }
        $queryString = $uriParts[0];
        if (count($uriParts)) {
            $queryString = $queryString . "?";
        }
        ksort($sortMap);
        $querySeprator = '&';
        foreach ($sortMap as $sortMapKey => $sortMapValue) {
            $queryString = $queryString . $sortMapKey;
            if (isset( $sortMapValue )) {
                $queryString = $queryString . "=" . $sortMapValue;
            }
            $queryString = $queryString . $querySeprator;
        }
        if (null == count($sortMap)) {
            $queryString = substr($queryString, 0, strlen($queryString) - 1);
        }

        return $queryString;
    }


    private function splitSubResource($uri)
    {
        $queIndex = strpos($uri, "?");
        $uriParts = array();
        if (null != $queIndex) {
            array_push($uriParts, substr($uri, 0, $queIndex));
            array_push($uriParts, substr($uri, $queIndex + 1));
        } else {
            array_push($uriParts, $uri);
        }

        return $uriParts;
    }


    public function getPathParameters()
    {
        return $this->pathParameters;
    }


    public function putPathParameter($name, $value)
    {
        $this->pathParameters[$name] = $value;
    }


    public function getDomainParameter()
    {
        return $this->domainParameters;
    }


    public function putDomainParameters($name, $value)
    {
        $this->domainParameters[$name] = $value;
    }


    public function getUriPattern()
    {
        return $this->uriPattern;
    }


    public function setUriPattern($uriPattern)
    {
        return $this->uriPattern = $uriPattern;
    }
}
