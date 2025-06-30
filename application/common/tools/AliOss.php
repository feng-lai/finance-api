<?php
/**
 * Created by PhpStorm.
 * User: Airon
 * Date: 2016/11/17
 * Time: 17:21
 *
 */
namespace app\common\tools;


use OSS\OssClient;
use OSS\Core\OssException;
use think\Config;
use think\Exception;

class AliOss
{
    public function uploadOss($file, $name)
    {
        // 配置文件

        $oss_config      =  Config::get('alioss');
        $accessKeyId     = $oss_config['appid'];
        $accessKeySecret = $oss_config['appkey'];
        $endpoint        = $oss_config['endpoint'];
        $bucket          = $oss_config['bucket'];

        // 文件路径生成
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $result    = $ossClient->uploadFile($bucket, $name, $file);
            if (isset($result['info']['http_code']) AND $result['info']['http_code'] == 200) {
                $img_url = $result['info']['url'] ?? '';
                return $img_url;
            } else {
                throw new Exception(lang(40074), 40074);
            }
        } catch (OssException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }
}