#!/usr/bin/env php
<?php

function promoteme($update, $MadelineProto, $msg) {
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerUser':
        $peer = $MadelineProto->get_info($update['update']
        ['message']['from_id'])['bot_api_id'];
        $cont = "true";
        break;
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $cont = "true";
        break;
    }
    if ($cont == "true") {
        $msg_id = $update['update']['message']['id'];
        if (from_admin($update, $MadelineProto)) {
            $id = catch_id($update, $MadelineProto, $msg);
            $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
            $title = $MadelineProto->get_info(-100 . $update['update']['message']
            ['to_id']['channel_id'])['Chat']['title'];
            if ($id[0]) {
                $userid = $id[1];
                $username = $id[2];
                $mention = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                5, 'length' => strlen($username), 'user_id' => $userid]];
                if (!file_exists('promoted.json')) {
                    $json_data = [];
                    $json_data[$ch_id] = [];
                    file_put_contents('promoted.json', json_encode($json_data));
                }
                $file = file_get_contents("promoted.json");
                $promoted = json_decode($file, true);
                if (array_key_exists($ch_id, $promoted)) {
                    if (!in_array($userid, $promoted[$ch_id])) {
                        array_push($promoted[$ch_id], $userid);
                        file_put_contents('promoted.json', json_encode($promoted));
                        $message = "User ".$username." is now a moderator of ".$title;
                    } else {
                        $message = "User ".$username." is already a moderator of ".$title;
                    }
                } else {
                    $promoted[$ch_id] = [];
                    array_push($promoted[$ch_id], $userid);
                    file_put_contents('promoted.json', json_encode($promoted));
                    $message = "User ".$username." is now a moderator of ".$title;
                    }
            } else {
                $message = "I don't know of anyone called ".$message;
            }
        } else {
            $message = "Only the best get to promote people. You're not the best";
        }
        $sentMessage = $MadelineProto->messages->sendMessage
        (['peer' => $peer, 'reply_to_msg_id' =>
        $msg_id, 'message' => $message, 'entities' => $mention]);
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function demoteme($update, $MadelineProto, $msg) {
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerUser':
        $peer = $MadelineProto->get_info($update['update']
        ['message']['from_id'])['bot_api_id'];
        $cont = "true";
        break;
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $cont = "true";
        break;
    }
    if ($cont == "true") {
        $msg_id = $update['update']['message']['id'];
        if (from_admin($update, $MadelineProto)) {
            $id = catch_id($update, $MadelineProto, $msg);
            $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
            $title = $MadelineProto->get_info(-100 . $update['update']['message']
            ['to_id']['channel_id'])['Chat']['title'];
            if ($id[0]) {
                $userid = $id[1];
                $username = $id[2];
                $mention = [['_' => 'inputMessageEntityMentionName', 'offset' =>
                5, 'length' => strlen($username), 'user_id' => $userid]];
                if (!file_exists('promoted.json')) {
                    $json_data = [];
                    $json_data[$ch_id] = [];
                    file_put_contents('promoted.json', json_encode($json_data));
                }
                $file = file_get_contents("promoted.json");
                $promoted = json_decode($file, true);
                if (array_key_exists($ch_id, $promoted)) {
                    if (in_array($userid, $promoted[$ch_id])) {
                        if (($key = array_search($userid, $promoted[$ch_id]))
                        !== false) {
                            unset($promoted[$ch_id][$key]);
                        }
                        file_put_contents('promoted.json', json_encode($promoted));
                        $message = "User ".$username." is NO LONGER a moderator of ".$title;
                    } else {
                        $message = "User ".$username." never was a moderator of ".$title;
                    }
                } else {
                    $message = "User ".$username." never was a moderator of ".$title;
                    }
            } else {
                $message = "I don't know of anyone called ".$message;
            }
        } else {
            $message = "I think we should leave the humiliation of being demoted for admins, don't you?";
        }
        $sentMessage = $MadelineProto->messages->sendMessage
        (['peer' => $peer, 'reply_to_msg_id' =>
        $msg_id, 'message' => $message, 'entities' => $mention]);
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}