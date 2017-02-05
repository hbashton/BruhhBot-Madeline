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
function idme($update, $MadelineProto, $msg_arr)
{
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerUser':
        $peer = $MadelineProto->get_info(
            $update['update']
            ['message']['from_id']
        )['bot_api_id'];
        $noid = "Your Telegram ID is $peer";
        $cont = "true";
        break;
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(
            -100 . $update['update']['message']
            ['to_id']['channel_id']
        )['Chat']['title'];
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
            $id = catch_id($update, $MadelineProto, $msg_arr);
            if ($id[0]) {
                $username = $id[2];
                $userid = $id[1];
                $message = "The Telegram ID of $username is $userid";
            } else {
                $message = "I can't find a user called $msg_arr. Who's that?";
            }
        } else {
            if (array_key_exists('entities', $update['update']['message'])) {
                foreach ($update['update']['message']['entities'] as $key) {
                    if (array_key_exists('user_id', $key)) {
                        $userid = $key['user_id'];
                        $message = "The Telegram ID of $msg_arr is $userid";
                        break;
                    } else {
                        $message = "I can't find a user called $msg_arr. ".
                        "Who's that?";
                    }
                }
            }
            if (!isset($userid)) {
                $message = "I can't find a user called $msg_arr. Who's that?";
            }
        }
        if (!isset($message)) {
            $message = "I can't find a user called $msg_arr. Who's that?";
        }
        if (empty($msg_arr)) {
            $message = $noid;
        }
        $sentMessage = $MadelineProto->messages->sendMessage(
            ['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]
        );
        \danog\MadelineProto\Logger::log($sentMessage);

    }
}

function adminlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(
            -100 . $update['update']['message']
            ['to_id']['channel_id']
        )['Chat']['title'];
        $message = "Admins for $title"."\r\n";
        $messageEntityBold = ['_' => 'messageEntityBold', 'offset' => 11,
        'length' => strlen($title) ];
        $admins = $MadelineProto->channels->getParticipants(
            ['channel' => -100 . $update['update']['message']['to_id']['channel_id'],
            'filter' => $channelParticipantsAdmin, 'offset' => 0, 'limit' => 0]
        );
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
        $sentMessage = $MadelineProto->messages->sendMessage(
            ['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message,
            'entities' => $entity]
        );
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function modlist($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $channelParticipantsAdmin = ['_' => 'channelParticipantsAdmins'];
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(
            -100 . $update['update']['message']
            ['to_id']['channel_id']
        )['Chat']['title'];
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
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message,
                'entities' => $messageEntityBold]
            );
        }
        if (!isset($sentMessage)) {
            $entity = $entity_;
            $entity[] = $messageEntityBold;
            unset($entity_);
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message,
                'entities' => $entity]
            );
        }
        \danog\MadelineProto\Logger::log($sentMessage);
    }
}

function pinmessage($update, $MadelineProto, $silent)
{
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $mods = "I can pin messages. But YOU can't make me. :)";
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(
            -100 . $update['update']['message']
            ['to_id']['channel_id']
        )['Chat']['title'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                if (array_key_exists(
                    "reply_to_msg_id",
                    $update['update']['message']
                )
                ) {
                    $pin_id = $update['update']['message']['reply_to_msg_id'];
                    $pin = $MadelineProto->
                    channels->updatePinnedMessage(
                        ['silent' => $silent,
                        'channel' => $peer,
                        'id' => $pin_id ]
                    );
                    $message = "Message successfully pinned!";
                    \danog\MadelineProto\Logger::log($pin);
                    var_dump($silent);
                } else {
                    $entity = [['_' => 'messageEntityCode', 'offset' => 37,
                    'length' => 4 ], ['_' => 'messageEntityCode', 'offset' => 73,
                    'length' => 12 ]];
                    $message = "Pin a message by replying to it with /pin\r\n".
                    "to pin it silently, reply with /pin silent";
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        ['peer' => $peer, 'reply_to_msg_id' =>
                        $msg_id, 'message' => $message,
                        'entities' => $entity]
                    );
                }
            }
            if (isset($sentMessage)) {
                    \danog\MadelineProto\Logger::log($sentMessage);
            } else {
                $sentMessage = $MadelineProto->messages->sendMessage(
                    ['peer' => $peer, 'reply_to_msg_id' =>
                    $msg_id, 'message' => $message]
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}
