#!/usr/bin/env php
<?php

function from_master($update, $MadelineProto) {
    if ($MadelineProto->get_info($update['update']
    ['message']['from_id'])['bot_api_id'] == $MadelineProto->get_info(getenv
    ('TEST_USERNAME'))['bot_api_id']) {
        return true;
    } else {
        return false;
    }
}

function is_master($userid, $MadelineProto) {
    if ($userid == $MadelineProto->get_info(getenv
    ('TEST_USERNAME'))['bot_api_id']) {
        return true;
    } else {
        return false;
    }
}

function from_admin($update, $MadelineProto) {
    $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
    $admins = $MadelineProto->channels->getParticipants(
    ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
    'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]);
    $userid = $MadelineProto->get_info($update['update']
            ['message']['from_id'])['bot_api_id'];
    foreach ($admins['users'] as $key) {
                $adminid = $key['id'];
                if ($adminid == $userid) {
                    $mod = "true";
                    break;
                } else {
                    $mod = "false";
                }
            }
    if ($mod == "true" or from_master($update, $MadelineProto)) {
        return true;
    } else {
        return false;
    }
}

function is_bot_admin($update, $MadelineProto) {
    $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
    $admins = $MadelineProto->channels->getParticipants(
    ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
    'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]);
    $bot_id = $MadelineProto->API->datacenter->authorization['user']['id'];
    foreach ($admins['users'] as $key) {
                $adminid = $key['id'];
                if ($adminid == $bot_id) {
                    $mod = "true";
                    break;
                } else {
                    $mod = "false";
                }
            }
    if ($mod == "true") {
        return true;
    } else {
        return false;
    }
}

function from_mod($update, $MadelineProto) {
    $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
    $userid = $MadelineProto->get_info($update['update']
            ['message']['from_id'])['bot_api_id'];
    if (!file_exists('promoted.json')) {
        $json_data = [];
        $json_data[$ch_id] = [];
        file_put_contents('promoted.json', json_encode($json_data));
    }
    $file = file_get_contents("promoted.json");
    $promoted = json_decode($file, true);
    if (array_key_exists($ch_id, $promoted)) {
        if (in_array($userid, $promoted[$ch_id])) {
            $mod = "true";
        } else {
            $mod = "false";
        }
    }
    if ($mod == "true" or from_master($update, $MadelineProto)) {
        return true;
    } else {
        return false;
    }
}

function from_admin_mod($update, $MadelineProto) {
    if (from_mod($update, $MadelineProto) or from_admin($update, $MadelineProto)
    or from_master($update, $MadelineProto)) {
        return true;
    } else {
        return false;
    }
}