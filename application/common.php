<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
// 设置错误异常等级
error_reporting(E_ERROR | E_PARSE);

use think\Db;

/**
 * 统一api返回函数
 * @Author   CCH PYK
 * @DateTime 2020-05-23T12:19:19+0800  2021-6-13 09:40
 * @param    $code '结果编码'
 * @param    $msg '说明'
 * @param    $data '数据'
 * @return \think\Response  '统一格式返回json'
 */
function apiResult($code, $msg = '', $data = null)
{
    $result = ['code' => $code];
    !empty($msg) && $result['msg'] = $msg;
    if (!is_null($data)) {
        is_object($data) && $data = $data->toarray();
        // if (is_array($data)) {
        //   array_walk_recursive($data,function(&$data_v,$key){
        //     if($data_v === null){ $data_v = ''; }
        //   });
        // }
        if (isset($data['data'])) {
            $result['result'] = $data;
        } else {
            $result['result'] = array();
            $result['result']['data'] = $data;
        }
    }
    return json($result);
}

/**
 * 封装apiResult判断返回
 * @param $result
 * @param $ok_msg
 * @param $error_msg
 * @return \think\Response
 * @Author PYK
 * @Datetime 2021/6/23 15:50:25
 */
function jsonResult($result)
{
    if ($result['status'] == 1) {
        return apiResult('2000', $result['msg'], $result['data']);
    }
    if ($result['status'] == 2) {
        return apiResult('5001', $result['msg'], $result['data']);
    }
    return apiResult('5000', $result['msg']);
}

/**
 * 上传文件
 * @Author   CCH
 * @DateTime 2020-05-23T12:20:41+0800
 * @param file   file文件对象
 * @return   上传结果
 */
function uploadFile($file_name, $tmp_name, $path = '')
{
    try {
        $config = config('config.aliyun_oss');
        $object = $path . uuidCreate() . '.' . pathinfo($file_name, PATHINFO_EXTENSION);
        $oss = new \OSS\OssClient($config['keyId'], $config['keySecret'], $config['endpoint']);
        $result = $oss->uploadFile($config['bucket'], $object, $tmp_name);
        return ['status' => 1, 'data' => $object];
    } catch (OssException $e) {
        return ['status' => 0, 'msg' => $e->getMessage()];
    }
    return true;
}

/**
 * 上传文件
 * @Author   CCH
 * @DateTime 2020-05-23T12:20:41+0800
 * @param file   file文件对象
 * @return   上传结果
 */
function uploadFileExcel($file_name, $tmp_name, $path = '')
{
    try {
        $config = config('config.aliyun_oss');
        //$object = $path . $file_name;
        $oss = new \OSS\OssClient($config['keyId'], $config['keySecret'], $config['endpoint']);
        $result = $oss->uploadFile($config['bucket'], $object, $tmp_name);
        return ['status' => 1, 'data' => $object];
    } catch (OssException $e) {
        return ['status' => 0, 'msg' => $e->getMessage()];
    }
    return true;
}


/**
 * 上传文件（字符串形式）
 * @Author   CCH
 * @DateTime 2020-05-23T12:20:41+0800
 * @param 文件内容
 * @return   上传结果
 */
function uploadFileString($content, $path = '')
{
    try {
        $config = config('config.aliyun_oss');
        $object = $path . uuidCreate() . '.png';
        $oss = new \OSS\OssClient($config['keyId'], $config['keySecret'], $config['endpoint']);
        $result = $oss->putObject($config['bucket'], $object, $content);
        return ['status' => 1, 'data' => $object];
    } catch (OssException $e) {
        return ['status' => 0, 'msg' => $e->getMessage()];
    }
    return true;
}

/**
 * 生成UUID
 * @Author   CCH
 * @DateTime 2020-05-23T11:59:13+0800
 * @return   UUID
 */
function uuidCreate()
{
    if (function_exists('com_create_guid')) {
        $uuid = com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
        // $charid = strtoupper(md5(uniqid(rand(), true)));
        $charid = md5(uniqid(rand(), true));
        $hyphen = chr(45);
        $uuid = chr(123)
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);
    }
    $uuid = str_replace(array('{', '}', '-'), '', $uuid);
    return $uuid;
}

/**
 * 生成编号
 * @Author   CCH
 * @DateTime 2020-05-23T11:59:13+0800
 * @return   编号
 */
function numberCreate()
{
    return date('YmdHis') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 4);
}

/**
 * @desc 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6371000;
    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;

    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;

    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}

/**
 * 二位数组根据某个字段排序
 * @Author   CCH
 * @DateTime 2020-05-24T13:14:15+0800
 * @param    $arr        排序数组
 * @param    $field_name 字段名
 * @param    $sort_type  排序方式
 * @return   排序后数组
 */
function arrSortByField($arr, $field_name, $sort_type = SORT_ASC)
{
    $fields = array_column($arr, $field_name);
    array_multisort($fields, $sort_type, $arr);
    return $arr;
}

/**
 * 空默认
 * @Author   cch
 * @DateTime 2020-05-25T17:33:56+0800
 * @param    $val     值
 * @param    $default 空时替换的默认值
 * @return   [type]
 */
function emptyDef($val, $default = "")
{
    return empty($val) ? $default : $val;
}


/**
 * 检验数据的真实性，并且获取解密后的明文.
 * @param $encrypted_data string 加密的用户数据
 * @param $iv string 与用户数据一同返回的初始向量
 * @param $data string 解密后的原文
 *
 * @return int 成功0，失败返回对应的错误码
 */
function xcxDecryptData($session_key, $encrypted_data, $iv)
{
    if (strlen($session_key) != 24) {
        return ['status' => 0, 'msg' => '错误sessionKey'];
    }
    $aesKey = base64_decode($session_key);
    if (strlen($iv) != 24) {
        return ['status' => 0, 'msg' => '错误iv'];
    }
    $aesIV = base64_decode($iv);
    $aesCipher = base64_decode($encrypted_data);
    $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
    $data = json_decode($result, true);
    if (empty($data)) {
        return ['status' => 0, 'msg' => '解密失败'];
    }
    return ['status' => 1, 'data' => $data];
}


/**
 * 补位函数
 * @Author   cch
 * @DateTime 2020-05-29T15:23:55+0800
 * @param    $str  原字符串
 * @param    $len  新字符串长度
 * @param    $msg  填补字符
 * @param string $type [description]
 * @return         [description]
 */
function dispRepair($str, $len, $msg = '0')
{
    $length = $len - strlen($str);
    if ($length < 1) return $str;
    $str = str_repeat($msg, $length) . $str;
    return $str;
}

/**
 * 计算时间差/两个时间日期相隔的天数,时,分,秒
 * @Author   cch
 * @DateTime 2020-06-03T10:12:40+0800
 * @param    $begin_time [description]
 * @param    $end_time [description]
 * @return               [description]
 */
function timeDiff($begin_time, $end_time)
{
    if ($begin_time < $end_time) {
        $starttime = $begin_time;
        $endtime = $end_time;
    } else {
        $starttime = $end_time;
        $endtime = $begin_time;
    }
    $timediff = $endtime - $starttime;
    $days = intval($timediff / 86400);
    $remain = $timediff % 86400;
    $hours = intval($remain / 3600);
    $remain = $remain % 3600;
    $mins = intval($remain / 60);
    $secs = $remain % 60;
    $res = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
    return $res;
}

/**
 * 图片加上阿里云访问链接
 * @Author   cch
 * @DateTime 2020-06-05T17:33:27+0800
 * @param    $image [description]
 * @return          [description]
 */
function imagesAddAccessUrl($image)
{
    if (empty($image)) {
        return '';
    }
    $access_url = config('config.aliyun_oss.access_url');
    return $access_url . $image;
}

/**
 * CURL请求
 * @Author   CCH
 * @DateTime 2020-06-07T17:14:32+0800
 * @param    $url [description]
 * @param string $data [description]
 * @param string $method [description]
 * @return           [description]
 */
function httpRequest($url, $data = '', $method = 'GET',$header=[])
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    if ($method == 'POST') {
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($data != '') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
    }
    if (!empty($header)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

/**
 * 发送短信
 * @Author   cch
 * @DateTime 2020-06-09T16:00:50+0800
 * @param    $mobile 手机
 * @param    $param  模板参数
 * @return           [description]
 */
function sendSms($mobile, $param = [], $template_code = '', $sign_name = '')
{
    if (empty($mobile)) {
        return false;
    }
    $config = config('config.aliyun_sms');
    // $config = [
    //   'keyId'=>Db::name('config')->where('key','SMS_KEY_ID')->value('value'),
    //   'keySecret'=>Db::name('config')->where('key','SMS_KEY_SECRET')->value('value'),
    //   'SignName'=>Db::name('config')->where('key','SMS_SIGN_NAME')->value('value')
    // ];
    \AlibabaCloud\Client\AlibabaCloud::accessKeyClient($config['keyId'], $config['keySecret'])
        ->regionId('cn-hangzhou')
        ->asDefaultClient();
    try {
        // $options = [
        //               'RegionId' => "cn-hangzhou",
        //               'PhoneNumbers' => $mobile,
        //               'SignName' => $config['SignName'],
        //               'TemplateCode' => $config['TemplateCode']
        //             ];
        $options = [
            'RegionId' => "cn-hangzhou",
            'PhoneNumbers' => $mobile,
            'SignName' => empty($sign_name) ? $config['SignName'] : $sign_name,
            'TemplateCode' => empty($template_code) ? $config['TemplateCode'] : $template_code
        ];
        if (!empty($param)) {
            $options['TemplateParam'] = json_encode($param);
        }
        $result = \AlibabaCloud\Client\AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->version('2017-05-25')
            ->action('SendSms')
            ->method('POST')
            ->host('dysmsapi.aliyuncs.com')
            ->options([
                'query' => $options,
            ])
            ->request();
        if ($result['Code'] == 'OK') {
            return ['status' => 1];
        } else {
            return ['status' => 0, 'msg' => $result['Message']];
        }
    } catch (\Exception $e) {
        return ['status' => 0, 'msg' => $e->getMessage()];
    }
}

// 格式化搜索筛选
function getSearchParam($params)
{
    $map = [];
    foreach ($params as $key => $param) {
        if (isSearchParam($param['key'])) {
            switch ($param['type']) {
                case '=':
                    $map[] = [$param['key'], '=', input($param['key'])];
                    break;
                case '>':
                    $map[] = [$param['key'], '>', input($param['key'])];
                    break;
                case '>=':
                    $map[] = [$param['key'], '>=', input($param['key'])];
                    break;
                case '<':
                    $map[] = [$param['key'], '<', input($param['key'])];
                    break;
                case '<=':
                    $map[] = [$param['key'], '<=', input($param['key'])];
                    break;
                case 'like':
                    $map[] = [$param['key'], 'like', '%' . input($param['key']) . '%'];
                    break;
                default:
                    break;
            }
        }
    }
    return $map;
}

// 判断是否是筛选的参数
function isSearchParam($param)
{
    return input($param, null) != null;
}

// 参数过滤（筛选）
function paramFilter($params, $fields = [])
{
    $result = [];
    foreach ($fields['must'] as $k => $field) {
        if (empty($params[$field]) && $params[$field] != '0') {
            return ['error_msg' => '参数不能为空(' . emptyDef($fields['promps'][$field],$field) . ')'];
        }
        $result[$field] = $params[$field];
    }
    // foreach ($fields['onemust'] as $k => $field_list) {
    //     $is_exist = false;
    //     foreach ($field_list as $field) {
    //         if ( !empty($params[$field]) ) {
    //             $result[$field] = $params[$field];
    //             $is_exist = true;
    //             break;
    //         }
    //     }
    //     if (!$is_exist) {
    //         return ['error_msg'=>'参数不能为空('.implode(' | ', $field_list).')'];
    //     }
    // }
    foreach ($fields['nomust'] as $k => $field) {
        isset($params[$field]) && $result[$field] = $params[$field];
    }
    return $result;
}

function checkParam($params, $fields = [])
{
    $result = [];
    foreach ($fields as $field => $name) {
        if (empty($params[$field]) && $params[$field] != '0') {
            return ['error_msg' => '参数不能为空(' . $name . ')'];
        }
    }
    return $result;
}


/**
 * 参数过滤
 * @param $params '参数'
 * @param array $fields '检查字段，维数组'
 * @Author PYK
 * @Datetime 2021/6/13 9:24:27
 * @return mixed;
 */
function checkParamIsEmpty($params, $fields = [])
{
    foreach ($fields as $value) {
        $tempArr = explode('|', $value);
        foreach ($tempArr as $k => $v) {
            if (empty($params[$v]) && $params[$v] != '0' && $k + 1 == count($tempArr)) {
                return ['status' => 0, 'msg' => lang('parameter_cannot_be_empty').'(' . $v . ')'];
            }
        }
    }
    return ['status' => 1, 'msg' => 'Success'];
}

/**
 * 参数过滤--异常抛出
 * @param $params '参数'
 * @param array $fields '检查字段，维数组'
 * @Author PYK
 * @Datetime 2021/6/13 9:24:27
 * @throws Exception
 */
function checkParamIsEmptyByExit($params, $fields = [])
{
    $result = checkParamIsEmpty($params, $fields);
    if ($result['status'] == 0) {
//        exception($result['msg'], 400);
        abort(400, $result['msg']);
        exit();
    }
}

/**
 * 参数过滤--异常抛出
 * @param array $fields '检查字段，维数组'
 * @Author PYK
 * @Datetime 2021/6/13 9:24:27
 * @throws Exception
 */
function checkInputEmptyByExit($fields = [])
{
    if (is_string($fields)) $fields = explode('|', $fields);
    checkParamIsEmptyByExit(input(), $fields);
}

// 判断是否为空
function isEmpty($val)
{
    return empty($val) && $val != '0';
}

// 抽取数组指定字段
function extractArrayByFields($arr, $fields)
{
    $result = [];
    foreach ($fields as $field) {
        $result[$field] = $arr[$field];
    }
    return $result;
}

// xml转数组
function xmlToArray($xml)
{
    return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
}

function myExplode($str, $separator = ',')
{
    if (!empty($str)) {
        return explode($separator, $str);
    }
    return [];
}

/**
 * 生成二维码
 * @Author   CCH
 * @DateTime 2020-06-07T17:25:21+0800
 * @param    $path  路径
 * @param    $width 宽度
 * @return   返回图片base数据给前端小程序，直接放前端img src即可显示
 */
function createQrCodeOss($path, $width = 150)
{
    $config = config('config.wxparam');
    $access_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $config['appid'] . '&secret=' . $config['secret'];
    $access_token = httpRequest($access_token_url);
    $access_token = json_decode($access_token, true);
    if (empty($access_token['access_token'])) {
        return apiResult('5000', 'access_token获取失败');
    }

    $qcode = "https://api.weixin.qq.com/wxa/getwxacode?access_token=" . $access_token['access_token'];
    $param = json_encode(array("path" => $path, "width" => 150));
    $result = httpRequest($qcode, $param, "POST");
    if (!empty($result)) {
        $image = uploadFileString($result, 'appraisal/app/');
        if ($image['status'] == 1) {
            return ['status' => 1, 'data' => $image['data']];
        } else {
            return ['status' => 0, 'msg' => '上传失败'];
        }
    } else {
        return ['status' => 0, 'msg' => '二维码生成失败'];
    }
}

/**
 * 获取input数据
 * @return array
 * @Author PYK
 * @Datetime 2021/6/12 21:51:40
 */
function getInputData()
{
    $param = file_get_contents('php://input');
    return json_decode($param, true);
}

/**
 * 删除查询map存在的字段
 * @param $map array
 * @param $need array | string
 * @Author PYK
 * @Datetime 2021/6/14 11:09:45
 */
function unsetMap(&$map, $need)
{
    foreach ($map as $k => $v) {
        if (is_array($need)) {
            if (in_array($v[0], $need)) unset($map[$k]);
        } else {
            if ($v[0] == $need) unset($map[$k]);
        }
    }
}

/**
 * 查询map是否存在指定的字段
 * @param $map array
 * @param $field string
 * @Author PYK
 * @Datetime 2021/6/14 11:09:45
 * @return bool
 */
function mapIsExistField($map, $field)
{
    foreach ($map as $k => $v) {
        if ($v[0] == $field) return true;
    }
    return false;
}

/**
 * 更新map查询字段
 * @param $map array
 * @param $oldField string
 * @param $newField string
 * @Author PYK
 * @Datetime 2021/6/14 11:09:45
 */
function mapUpdateField(&$map, $oldField, $newField = null)
{
    if (is_string($oldField)) {
        foreach ($map as $k => $v) {
            if ($v[0] == $oldField) $map[$k][0] = $newField;
        }
    }

}

/**
 * 更新map查询条件
 * @param $map array
 * @param $field string
 * @param $newCondition string
 * @Author PYK
 * @Datetime 2021/6/14 11:09:45
 */
function mapUpdateCondition(&$map, $field, $newCondition = null)
{
    if (is_string($field)) {
        foreach ($map as $k => $v) {
            if ($v[0] == $field) $map[$k][1] = $newCondition;
        }
    }
}

/**
 * 数据库查询条件插入表前缀。
 * @param $map array
 * @param $prefix string
 * @Author PYK
 * @Datetime 2021/6/14 11:09:45
 */
function mapInsertPrefix(&$map, $prefix = 'a')
{
    foreach ($map as $k => $v) {
        //排除json类型的筛选条件
        if (strpos($v[0], '->') === false) $v[0] = $prefix . '.' . $v[0];
        $map[$k][0] = $v[0];
    }
}


/**
 * 修改查询条件表前缀 列子：mapUpdatePrefix($map, ['b'=>'course_type']);
 * @param $map array
 * @param $prefixArr array
 * @Author PYK
 * @Datetime 2021/6/14 11:09:45
 */
function mapUpdatePrefix(&$map, $prefixArr)
{
    foreach ($map as $key => $value) {
        if (strpos($value[0], '->') !== false) continue; //排除json类型的修改
        $field = substr($value[0], strpos($value[0], '.') + 1);
        //排查json类型的筛选条件
        foreach ($prefixArr as $k => $v) {
            $flag = false;
            $to_filed = '';
            if (is_array($v)) {
                foreach ($v as $item) {
                    if ($field == $item) {
                        $to_filed = $k . '.' . $item;
                        $flag = true;
                    }
                }
            }
            if (is_string($v)) {
                if ($field == $v) {
                    $to_filed = $k . '.' . $v;
                    $flag = true;
                }
            }
            if ($flag) {
                $map[$key][0] = $to_filed;
                break;
            }
        }
    }
}

/**
 * 根据字段筛选查询Map
 * @param $fields
 * @return array
 * @Author PYK
 * @Datetime 2021/6/14 15:23:08
 */
function getInputEqualMap($fields)
{
    $map = [];
    if (is_string($fields)) {
        if (input($fields) != null){
            $map[] = [$fields, '=', input($fields)];
        }
        return $map;
    }
    if (is_array($fields))
        foreach ($fields as $v) {
            if (input($v) != null)
            $map[] = [$v, '=', input($v)];
        }
    return $map;
}


/**
 * 根据字段名称获取map值
 * @param $map array
 * @param $field string
 * @Author PYK
 * @Datetime 2021/6/14 11:09:45
 * @return mixed|null
 */
function getMapValueByFiled($map, $field)
{
    foreach ($map as $k => $v) {
        //排除json类型的筛选条件
        if ($v[0] == $field) {
            return $v[2];
        }
    }
    return null;
}


/**
 * 获取评分名称
 * @param $score
 * @return string
 * @Author PYK
 * @Datetime 2021/7/19 17:34:27
 */
function getCommentScoreName($score)
{
    $score_name_list = ['暂无评论','非常差', '差', '一般', '满意', '非常满意'];
    $max = 0;
    for ($i = 0; $i < count($score_name_list); $i++)
    {
        if ($score >= $i) $max = $i;
    }
    return $score_name_list[$max];
}

function dd($value){
    dump($value); die;
}