<?php
/**
 * Created by PhpStorm.
 * User: Airon
 * Date: 2016/11/17
 * Time: 17:21
 *
 */
namespace app\common\tools;



use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use think\Config;
use think\Exception;

class Ffmpeg
{
    public function gifCreate($file, $name,$osskey)
    {
//        $config = [
//            'ffmpeg.binaries'  => '/usr/local/Cellar/ffmpeg',
//            'ffprobe.binaries' =>  '/usr/local/Cellar/ffmpeg'
//        ];
        // 配置文件
        $ffmpeg = \FFMpeg\FFMpeg::create();
//        var_dump(1);die;
        $video  =  $ffmpeg ->open($file);
        $config = config('alioss');
        $ossUrl = $config['url'];
        $url = $ossUrl . $osskey . "?x-oss-process=image/info";
        $info = curl_send($url);
        $info = json_decode($info, true);
        $width = intval($info['ImageHeight']['value'] ?? 400);
        $height = intval($info['ImageWidth']['value'] ?? 300);

        $video ->gif(TimeCode::fromSeconds(3), new Dimension($width,$height), 2)->save( $file.'.gif' );
        //上传到oss
        $oss          = new AliOss();
        $oss->uploadOss($file.'.gif',  $name.".gif");
    }
}