#!/usr/bin/env php
<?php

function adminlist($update, $MadelineProto) {
        if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(-100 . $update['update']['message']
        ['to_id']['channel_id'])['Chat']['title'];
        $message = "Admins for $title"."\r\n";
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 11,
        'length' => strlen($title) ];
        $admins = $MadelineProto->channels->getParticipants(
    ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
        'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0, ]);
        foreach ($admins['users'] as $key) {
            $adminid = $key['id'];
            if (array_key_exists('username', $key)) {
            $adminname = "@".$key['username'];
            } else {
            $adminname = $key['first_name'];
            }
            if (!isset($entity_)) {
                $offset = strlen($message);
                $entity_ = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                $offset, 'length' => strlen($adminname), 'user_id' =>
                $adminid]];
                $length = $offset + strlen($adminname) + 2;
                $message = $message.$adminname."\r\n";
                var_dump($length);
            } else {
                $entity_[] = ['_' =>
                'inputMessageEntityMentionName', 'offset' => $length,
                'length' => strlen($adminname), 'user_id' => $adminid];
                $length = $length + 2 + strlen($adminname);
                $message = $message.$adminname."\r\n";
            }
        }
        $entity = $entity_;
        $entity[] = $messageEntityBold;
        var_dump($entity_, true);
        unset($entity_);
        $sentMessage = $MadelineProto->messages->sendMessage
        (['peer' => $peer, 'message' => $message,
        'entities' => $entity]);
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

