<?php
/**
 * Created by PhpStorm.
 * User: Airon
 * Date: 2016/11/17
 * Time: 17:21
 *
 */
namespace app\common\tools;

use think\Cache;
use think\Db;
use think\Exception;
use JPush\Client as JPushSdk;
class JPush
{
    public function push($title,$content,$user_uuid,$logicArray){
        $app_key="6c6d44086c92ab3a9d8bd658";
        $master_secret="535c34f89b42573a6ab0f6d7";

        $client = new JPushSdk($app_key, $master_secret);
        $alias = $user_uuid;
        // $alias = '8a5a03a6e4bc10b2312b32032b7ee96d';
        try{
            // $response = $client->push()->setPlatform(array('ios','android'))->addAlias($alias)
            // ->iosNotification($content,array(
            //     'extras'=>$logicArray
            // ))
            // ->
            // ->send();

            // 保存用户消息
            $msg_data = [
                'uuid'=>uuidCreate(),
                'user_uuid'=>$user_uuid,
                'type'=>$logicArray['type'],
                'param'=>$logicArray,
                'content'=>$content,
                'create_time'=>date('Y-m-d H:i:s')
            ];
            unset($logicArray['type']);
            $msg_data['param'] = json_encode($msg_data['param'],JSON_UNESCAPED_UNICODE);
            if (isset($logicArray['location'])) {
                $msg_data['location'] = $logicArray['location'];
                unset($logicArray['location']);
            }
            Db::name('user_msg')->insert($msg_data);

  
            $response = $client->push()->setPlatform('all')->addAlias($alias)
            ->iosNotification($content,[
                'extras'=>$logicArray
            ])
            ->androidNotification($content,[
                'title' => $title,
                'builder_id' => 1,
                'extras' => $logicArray ]
            )->send();
            // dump($alias);
            // dump($response);die;
            return true;
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            // dump($e->getMessage());die;
            return false;
//            return self::returnmsg(403,[],[],'Internal Server Error',$e,"推送失败~");
        } catch (\JPush\Exceptions\APIRequestException $e) {
            // dump($e->getMessage());die;
            return false;
        }catch (\Exception $e) {
            // 更新失败 回滚事务
            return ['status'=>0,'msg'=>$e->getMessage()];
        }
    }

    public function push_test($title,$content,$user_uuid,$logicArray){
        $app_key="6c6d44086c92ab3a9d8bd658";
        $master_secret="535c34f89b42573a6ab0f6d7";

        $client = new JPushSdk($app_key, $master_secret);
        $alias = $user_uuid;
        // $alias = '8a5a03a6e4bc10b2312b32032b7ee96d';
        try{
            // $response = $client->push()->setPlatform(array('ios','android'))->addAlias($alias)
            // ->iosNotification($content,array(
            //     'extras'=>$logicArray
            // ))
            // ->
            // ->send();
  
            $response = $client->push()->setPlatform('all')->addAlias($alias)
            ->iosNotification($content,[
                'extras'=>$logicArray
            ])
            ->androidNotification($content,[
                'title' => $title,
                'builder_id' => 1,
                'extras' => $logicArray ]
            )->send();
            // dump($alias);
            // dump($response);die;
            return true;
        } catch (\JPush\Exceptions\APIConnectionException $e) {
            // dump($e->getMessage());die;
            return $e->getMessage();
//            return self::returnmsg(403,[],[],'Internal Server Error',$e,"推送失败~");
        } catch (\JPush\Exceptions\APIRequestException $e) {
            // dump($e->getMessage());die;
            return $e->getMessage();
        }
    }
}