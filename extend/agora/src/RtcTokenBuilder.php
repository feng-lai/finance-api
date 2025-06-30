<?php

namespace agora\src;


class RtcTokenBuilder {
    const RoleAttendee   = 0;
    const RolePublisher  = 1;
    const RoleSubscriber = 2;
    const RoleAdmin      = 101;


    public static function buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs) {
        return RtcTokenBuilder::buildTokenWithUserAccount($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpireTs);
    }


    public static function buildTokenWithUserAccount($appID, $appCertificate, $channelName, $userAccount, $role, $privilegeExpireTs) {
        $token = AccessToken::init($appID, $appCertificate, $channelName, $userAccount);
        $Privileges = AccessToken::Privileges;
        $token->addPrivilege($Privileges["kJoinChannel"], $privilegeExpireTs);
        if (
            ($role == RtcTokenBuilder::RoleAttendee) || ($role == RtcTokenBuilder::RolePublisher) || ($role == RtcTokenBuilder::RoleAdmin)) {
            $token->addPrivilege($Privileges["kPublishVideoStream"], $privilegeExpireTs);
            $token->addPrivilege($Privileges["kPublishAudioStream"], $privilegeExpireTs);
            $token->addPrivilege($Privileges["kPublishDataStream"], $privilegeExpireTs);
        }
        return $token->build();
    }
}


?>