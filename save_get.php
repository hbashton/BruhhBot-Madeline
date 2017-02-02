#!/usr/bin/env php
<?php

function saveme($update, $MadelineProto, $msg, $name) {
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
        if (from_admin_mod($update, $MadelineProto)) {
            $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
            $title = $MadelineProto->get_info(-100 . $update['update']['message']
            ['to_id']['channel_id'])['Chat']['title'];
            if (!file_exists('saved.json')) {
                $json_data = [];
                $json_data[$ch_id] = [];
                file_put_contents('saved.json', json_encode($json_data));
            }
            $file = file_get_contents("saved.json");
            $saved = json_decode($file, true);
            if (array_key_exists($ch_id, $saved)) {
                $saved[$ch_id][$name] = $msg;
                file_put_contents('saved.json', json_encode($saved));
                $message = "Message ".$name." has been saved";
                $code = [['_' => 'messageEntityBold', 'offset' => 8,
                'length' => strlen($name)]];
                $sentMessage = $MadelineProto->messages->sendMessage
                (['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $code]);
            } else {
                $saved[$ch_id] = [];
                $saved[$ch_id][$name] = $msg;
                file_put_contents('saved.json', json_encode($saved));
                $message = "Message ".$name." has been saved";
                $code = [['_' => 'messageEntityBold', 'offset' => 8,
                'length' => strlen($name)]];
                $sentMessage = $MadelineProto->messages->sendMessage
                (['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $code]);
            }
        } else {
            $message = "Only mods get to save stuff.";
        }
        if (!isset($sentMessage)) {
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]);
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function getme($update, $MadelineProto, $name) {
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
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        if (!file_exists('saved.json')) {
            $json_data = [];
            $json_data[$ch_id] = [];
            file_put_contents('saved.json', json_encode($json_data));
        }
        $file = file_get_contents("saved.json");
        $saved = json_decode($file, true);
        if (array_key_exists($ch_id, $saved)) {
            foreach ($saved[$ch_id] as $i => $ii) {
                if ($i == $name) {
                    $message = $name.":"."\r\n".$saved[$ch_id][$i];
                }
            }
            if (isset($message)) {
                $entity = [['_' => 'messageEntityBold', 'offset' => 0,
                'length' => strlen($name)]];
                $sentMessage = $MadelineProto->messages->sendMessage
                (['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message, 'entities' => $entity]);
            }
        }
        if (isset($sentMessage)) {
        \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}