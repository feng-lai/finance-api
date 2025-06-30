<?php
/**
 * Created by Terry.
 * User: Terry
 * Email: terr_exchange@outlook.com
 * Date: 2020/10/10
 * Time: 9:45
 */

namespace app\common\tools;

class SaveRemoteImage
{
    private $stream;

    private $dir;

    private $filename;

    private $url;

    public function __construct($path,$url)
    {
        $this -> dir = $path;
        $this -> url = $url;
        $this->filename = uuid().'.jpeg';
    }

    public function save()
    {
        ob_start();
        readfile($this -> url);
        $this -> stream = ob_get_contents();
        ob_end_clean();

        $file = fopen(ROOT_PATH.'public'.$this ->dir.DS.$this->filename,'w');
        fwrite($file,$this -> stream);
        fclose($file);

        $photo = "app_upload" . '/' .uuid().'.jpg';
        $oss          = new AliOss();
        $oss->uploadOss(self::getFilePath(),  $photo);
        unlink(self::getFilePath());

        return $photo;
    }

    public function getFilePath()
    {
        return ROOT_PATH.'public'.$this ->dir.DS.$this->filename;
    }

}