#!/usr/bin/env php
<?php

function idme($update, $MadelineProto, $msg_arr) {
    switch ($update['update']['message']['to_id']['_']) {
        case 'peerUser':
            $peer = $MadelineProto->get_info($update['update']
            ['message']['from_id'])['bot_api_id'];
            $noid = "Your Telegram ID is $peer";
            $cont = "true";
            break;
        case 'peerChannel':
            $peer = $MadelineProto->
            get_info($update['update']['message']['to_id'])
            ['InputPeer'];
            $title = $MadelineProto->get_info(-100 . $update['update']['message']
            ['to_id']['channel_id'])['Chat']['title'];
            $ch_id = $update['update']['message']['to_id']['channel_id'];
            $noid = "The Telegram ID of ".$title." is ".$ch_id;
            $cont = "true";
            break;
    }
    if (isset($cont)) {

        var_dump($msg_arr);
        $msg_id = $update['update']['message']['id'];
        $first_char = substr($msg_arr, 0, 1);
        if (preg_match_all('/@/', $first_char, $matches)) {
            $catch = catch_id($update, $MadelineProto, $msg_arr);
            if ($catch[0]) {
                $username = $catch[2];
                $userid = $catch[1];
                $message = "The Telegram ID of ".$username." is ".$userid;
            } else {
                $message = "I can't find a user called ".$msg_arr.". Who's that?";
            }
        } else {
            if (array_key_exists('entities', $update['update']['message'])) {
                foreach ($update['update']['message']['entities'] as $key) {
                    if (array_key_exists('user_id', $key)) {
                        $userid = $key['user_id'];
                        $message = "The Telegram ID of ".$msg_arr." is ".$userid;
                        break;
                    } else {
                        $message = "I can't find a user called ".$msg_arr.". Who's that?";
                    }
                }
            }
            if (!isset($userid)) {
                $message = "I can't find a user called ".$msg_arr.". Who's that?";
            }
        }
        if (!isset($message)) {
            $message = "I can't find a user called ".$msg_arr.". Who's that?";
        }
        if (empty($msg_arr)) {
            $message = $noid;
        }
        $sentMessage = $MadelineProto->messages->sendMessage
    (['peer' => $peer, 'reply_to_msg_id' =>
    $msg_id, 'message' => $message]);
    \danog\MadelineProto\Logger::log($sentMessage);

    }
}

function adminlist($update, $MadelineProto) {
    $msg_id = $update['update']['message']['id'];
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
        'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]);
        foreach ($admins['users'] as $key) {
            $adminid = $key['id'];
            if (array_key_exists('username', $key)) {
            $adminname = $key['username'];
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
    unset($entity_);
    $sentMessage = $MadelineProto->messages->sendMessage
    (['peer' => $peer, 'reply_to_msg_id' =>
    $msg_id, 'message' => $message,
    'entities' => $entity]);
    \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function modlist($update, $MadelineProto) {
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(-100 . $update['update']['message']
        ['to_id']['channel_id'])['Chat']['title'];
        $message = "Moderators for $title:"."\r\n";
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 0,
        'length' => 15 + strlen($title) ];
        if (!file_exists('promoted.json')) {
            $json_data = [];
            $json_data[$ch_id] = [];
            file_put_contents('promoted.json', json_encode($json_data));
        }
        $file = file_get_contents("promoted.json");
        $promoted = json_decode($file, true);
        if (array_key_exists($ch_id, $promoted)) {
            foreach ($promoted[$ch_id] as $i => $key) {
                $user = $MadelineProto->get_info($key)['User'];
                if (array_key_exists('username', $user)) {
                    $username = $user['username'];
                } else {
                    $username = $user['first_name'];
                }
                if (!isset($entity_)) {
                    $offset = strlen($message);
                    $entity_ = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                    $offset, 'length' => strlen($username), 'user_id' =>
                    $key]];
                    $length = $offset + strlen($username) + 2;
                    $message = $message.$username."\r\n";
                } else {
                    $entity_[] = ['_' =>
                    'inputMessageEntityMentionName', 'offset' => $length,
                    'length' => strlen($username), 'user_id' => $key];
                    $length = $length + 2 + strlen($username);
                    $message = $message.$username."\r\n";
                }
            }
        }
        if (!isset($entity_)) {
            $messageEntityBold = [['_' => 'messageEntityBold', 'offset' => 28,
            'length' => strlen($title) ]];
            $message = "There are no moderators for ".$title;
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message,
            'entities' => $messageEntityBold]);
        }
        if (!isset($sentMessage)) {
            $entity = $entity_;
            $entity[] = $messageEntityBold;
            unset($entity_);
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message,
            'entities' => $entity]);
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}
