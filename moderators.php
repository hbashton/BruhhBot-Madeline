<?php
/**
    Copyright (C) 2016-2017 Hunter Ashton

    This file is part of BruhhBot.

    BruhhBot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    BruhhBot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
function from_master($update, $MadelineProto)
{
    if ($MadelineProto->get_info(
        $update['update']
        ['message']['from_id']
    )['bot_api_id'] == $MadelineProto->get_info(
        getenv('TEST_USERNAME')
    )['bot_api_id']
    ) {
        return true;
    } else {
        return false;
    }
}

function is_master($MadelineProto, $userid)
{
    if ($userid == $MadelineProto->get_info(
        getenv('TEST_USERNAME')
    )['bot_api_id']
    ) {
        return true;
    } else {
        return false;
    }
}

function from_admin($update, $MadelineProto, $str = "", $send = false)
{
    $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
    $admins = $MadelineProto->channels->getParticipants(
        ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
        'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]
    );
    $userid = $MadelineProto->get_info(
        $update['update']
        ['message']['from_id']
    )['bot_api_id'];
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
        if ($send) {
            $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
            ['InputPeer'];
            $msg_id = $update['update']['message']['id'];
            $message = $str;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message]
            );
                \danog\MadelineProto\Logger::log($sentMessage);
        }
        return false;
    }
}

function is_admin($update, $MadelineProto, $userid)
{
    $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
    $admins = $MadelineProto->channels->getParticipants(
        ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
        'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]
    );
    foreach ($admins['users'] as $key) {
                $adminid = $key['id'];
        if ($adminid == $userid) {
            $mod = "true";
            break;
        } else {
            $mod = "false";
        }
    }
    if ($mod == "true" or is_master($MadelineProto, $userid)) {
        return true;
    } else {
        return false;
    }
}

function is_bot_admin($update, $MadelineProto)
{
    $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
    $admins = $MadelineProto->channels->getParticipants(
        ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
        'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]
    );
    $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
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
        $msg_id = $update['update']['message']['id'];
        $message = "I have to be an admin for this to work";
        $sentMessage = $MadelineProto->messages->sendMessage(
            ['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]
        );
            \danog\MadelineProto\Logger::log($sentMessage);
        return false;
    }
}

function from_mod($update, $MadelineProto)
{
    $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
    $userid = $MadelineProto->get_info(
        $update['update']
        ['message']['from_id']
    )['bot_api_id'];
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
        if ($mod == "true" or from_master($update, $MadelineProto)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function is_mod($update, $MadelineProto, $userid)
{
    $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
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
        if ($mod == "true" or is_master($MadelineProto, $userid)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function from_admin_mod($update, $MadelineProto, $str = "", $send = false)
{
    if (from_mod($update, $MadelineProto)
        or from_admin($update, $MadelineProto)
        or from_master($update, $MadelineProto)
    ) {
        return true;
    } else {
        if ($send) {
            $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
            ['InputPeer'];
            $msg_id = $update['update']['message']['id'];
            $message = $str;
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message]
            );
                \danog\MadelineProto\Logger::log($sentMessage);
        }
        return false;
    }
}

function is_admin_mod($update, $MadelineProto, $userid)
{
    if (is_mod($update, $MadelineProto, $userid) 
        or is_admin($update, $MadelineProto, $userid)
        or is_master($MadelineProto, $userid)
    ) {
        return true;
    } else {
        return false;
    }
}
