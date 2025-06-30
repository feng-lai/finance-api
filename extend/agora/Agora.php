<?php

namespace agora;

use agora\src\RtcTokenBuilder;
use DateTime;
use DateTimeZone;

class Agora {
    public static function build($params) {
        $config = config('agora.');
        $appID = $config['appid'];
        $appCertificate = $config['app_certificate'];
        $channelName = $params['channel_name'];// "7d72365eb983485397e3e3f9d460bdda";
        //        $uid = 2882341273;
        $uidStr = (string)$params['uid'];      // "2882341273";
        $role = RtcTokenBuilder::RoleAttendee;
        $expireTimeInSeconds = 3600;
        $currentTimestamp = (new DateTime("now", new DateTimeZone('UTC')))->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        //        $token = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);

        $token = RtcTokenBuilder::buildTokenWithUserAccount($appID, $appCertificate, $channelName, $uidStr, $role, $privilegeExpiredTs);

        return $token;
    }
}