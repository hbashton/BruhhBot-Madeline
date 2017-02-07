#!/usr/bin/env php
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
require 'MadelineProto/vendor/autoload.php';
require 'vendor/autoload.php';
require 'vendor/rmccue/requests/library/Requests.php';
require 'vendor/spatie/emoji/src/Emoji.php';
require_once 'banhammer.php';
require_once 'cache.php';
require_once 'check_msg.php';
require_once 'data_parse.php';
require_once 'id_.php';
require_once 'invite.php';
require_once 'lock.php';
require_once 'moderators.php';
require_once 'promote.php';
require_once 'save_get.php';
require_once 'set_info.php';
require_once 'settings.php';
require_once 'supergroup.php';
require_once 'time.php';
require_once 'weather.php';
$MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
Requests::register_autoloader();
if (file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
}

$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

if ($MadelineProto === false) {
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $checkedPhone = $MadelineProto->auth->checkPhone(
        [
            'phone_number'     => getenv('MTPROTO_NUMBER'),
        ]
    );
    \danog\MadelineProto\Logger::log($checkedPhone);
    $sentCode = $MadelineProto->phone_login(getenv('MTPROTO_NUMBER'));
    \danog\MadelineProto\Logger::log($sentCode);
    echo 'Enter the code you received: ';
    $code = fgets(
        STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']
        ['length'] : 5) + 1
    );
    $authorization = $MadelineProto->complete_phone_login($code);
    \danog\MadelineProto\Logger::log($authorization);
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize(
        'session.madeline',
        $MadelineProto
    ).' bytes'.PHP_EOL;

    echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize(
        'session.madeline'
    );
}
$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(
        ['offset' => $offset,
        'limit' => 50000, 'timeout' => 0]
    );
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        switch ($update['update']['_']) {
        case 'updateNewMessage':
            $res = json_encode($update, JSON_PRETTY_PRINT);
            if ($res == '') {
                $res = var_export($update, true);
            }
            var_dump($update);
            if (array_key_exists('message', $update['update']['message'])) {
                if ($update['update']['message']['message'] !== '') {
                    $first_char = substr(
                        $update['update']['message']
                        ['message'][0], 0, 1
                    );
                    if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                        $msg_str = substr(
                            $update['update']['message']
                            ['message'], 1
                        );
                            $msg_id = $update['update']['message']['id'];
                            $msg_arr = explode(' ', trim($msg_str));
                            switch ($msg_arr[0]) {
                        case 'time':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            $message = getloc($msg_str);
                            $peer = $MadelineProto->get_info(
                                $update['update']
                                ['message']['from_id']
                            )['bot_api_id'];
                            $sentMessage = $MadelineProto->messages->sendMessage(
                                ['peer' => $peer, 'message' => $message, 'entities'
                                => [['_' => 'messageEntityUnknown',
                                'offset' => 0, 'length' => strlen($message)]]]
                            );
                            \danog\MadelineProto\Logger::log($sentMessage);
                            break 2;

                        case 'weather':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            $message = getweather($msg_str);
                            $peer = $MadelineProto->get_info(
                                $update['update']
                                ['message']['from_id']
                            )['bot_api_id'];
                            $sentMessage = $MadelineProto->messages->sendMessage(
                                ['peer' => $peer, 'message' => $message, 'entities'
                                => [['_' => 'messageEntityUnknown',
                                'offset' => 0, 'length' => strlen($message)]]]
                            );
                            \danog\MadelineProto\Logger::log($sentMessage);
                            break 2;

                        case 'id':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            idme($update, $MadelineProto, $msg_str);
                            break 2;
                            }
                    }
                }
            }

            case 'updateNewChannelMessage':
                $res = json_encode($update, JSON_PRETTY_PRINT);
                var_dump($update);
                if (array_key_exists('message', $update['update']['message'])
                    && is_string($update['update']['message']['message'])
                ) {
                    $command = check_locked($update, $MadelineProto);
                    $check = new Exec($command);
                    $check->start();
                    $check->join();
                    $command = check_flood($update, $MadelineProto);
                    $check = new Exec($command);
                    $check->start();
                    $check->join();
                    if (isset($GLOBALS['from_user_chat_photo'])) {
                        set_chat_photo($update, $MadelineProto, false);
                    }
                    if (strlen($update['update']['message']['message']) !== 0) {
                        $first_char = substr(
                            $update['update']['message']['message'][0],
                            0, 1
                        );
                        if (preg_match_all('/#/', $first_char, $matches)) {
                                $msg_str = substr(
                                    $update['update']['message']['message'], 1
                                );
                                $msg_arr = explode(' ', trim($msg_str));
                                getme($update, $MadelineProto, $msg_arr[0]);
                        }
                        if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                                $msg_str = substr(
                                    $update['update']['message']['message'], 1
                                );
                                $msg_id = $update['update']['message']['id'];
                                $msg_arr = explode(' ', trim($msg_str));
                                switch ($msg_arr[0]) {
                            case 'time':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                $message = getloc($msg_str);
                                $peer = $MadelineProto->get_info(
                                    $update['update']
                                    ['message']['to_id']
                                )['InputPeer'];
                                $sentMessage = $MadelineProto->messages->sendMessage(
                                    ['peer' => $peer, 'reply_to_msg_id' =>
                                    $msg_id, 'message' => $message, 'entities'
                                    => [['_' => 'messageEntityUnknown',
                                    'offset' => 0, 'length' => strlen($message)]]]
                                );
                                \danog\MadelineProto\Logger::log($sentMessage);
                                break;

                            case 'weather':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                $message = getweather($msg_str);
                                $peer = $MadelineProto->get_info(
                                    $update['update']
                                    ['message']['to_id']
                                )['bot_api_id'];
                                $sentMessage = $MadelineProto->messages->sendMessage(
                                    ['peer' => $peer,
                                    'message' => $message,
                                    'entities' => [['_' => 'messageEntityUnknown',
                                    'offset' => 0,
                                    'length' => strlen($message)]]
                                    ]
                                );
                                \danog\MadelineProto\Logger::log($sentMessage);
                                break;

                            case 'adminlist':
                                adminlist($update, $MadelineProto);
                                break;

                            case 'kick':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                kickhim($update, $MadelineProto, $msg_str);
                                break;

                            case 'ban':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                banme($update, $MadelineProto, $msg_str);
                                break;

                            case 'settings':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                get_settings($update, $MadelineProto);
                                break;

                            case 'setflood':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                setflood($update, $MadelineProto, $msg_str);
                                break;

                            case 'modlist':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                modlist($update, $MadelineProto, $msg_str);
                                break;

                            case 'unban':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                unbanme($update, $MadelineProto, $msg_str);
                                break;

                            case 'promote':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                promoteme($update, $MadelineProto, $msg_str);
                                break;

                            case 'demote':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                demoteme($update, $MadelineProto, $msg_str);
                                break;

                            case 'save':
                                if (isset($msg_arr[1])) {
                                        $name = $msg_arr[1];
                                        unset($msg_arr[1]);
                                } else {
                                        $name = "";
                                }
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                saveme($update, $MadelineProto, $msg_str, $name);
                                break;

                            case 'pin':
                                if (isset($msg_arr[1])) {
                                    $msg_str = $msg_arr[1];
                                    if ($msg_str = "silent") {
                                        $silent = true;
                                    } else {
                                        $silent = false;
                                    }
                                } else {
                                    $silent = false;
                                }
                                pinmessage($update, $MadelineProto, $silent);
                                break;

                            case 'id':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                idme($update, $MadelineProto, $msg_str);
                                break;

                            case 'setphoto':
                                set_chat_photo($update, $MadelineProto);
                                break;

                            case 'public':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                public_toggle($update, $MadelineProto, $msg_str);
                                break;

                            case 'invite':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                invite_user($update, $MadelineProto, $msg_str);
                                break;

                            case 'addadmin':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                addadmin($update, $MadelineProto, $msg_str);
                                break;

                            case 'rmadmin':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                rmadmin($update, $MadelineProto, $msg_str);
                                break;

                            case 'setname':
                                unset($msg_arr[0]);
                                $msg_str = implode(" ", $msg_arr);
                                set_chat_title($update, $MadelineProto, $msg_str);
                                break;

                            case 'newlink':
                                export_new_invite($update, $MadelineProto);
                                break;

                            case 'newgroup':
                                unset($msg_arr[0]);
                                if (isset($msg_arr[1]) && isset($msg_arr[2])) {
                                        $title = $msg_arr[1];
                                        unset($msg_arr[1]);
                                        $about = implode(" ", $msg_arr);
                                } else {
                                        $title = "";
                                        $about = "";
                                }
                                create_new_supergroup(
                                    $update,
                                    $MadelineProto,
                                    $title,
                                    $about
                                );
                                break;

                            case 'lock':
                                if (isset($msg_arr[1])) {
                                        $name = $msg_arr[1];
                                        unset($msg_arr[1]);
                                } else {
                                        $name = "";
                                }
                                unset($msg_arr[0]);
                                lockme($update, $MadelineProto, $name);
                                break;

                            case 'unlock':
                                if (isset($msg_arr[1])) {
                                        $name = $msg_arr[1];
                                        unset($msg_arr[1]);
                                } else {
                                        $name = "";
                                }
                                unset($msg_arr[0]);
                                unlockme($update, $MadelineProto, $name);
                                break;

                            case 'end':
                                if (from_master($update, $MadelineProto)) {
                                        exit(0);
                                }
                                break;
                                }
                        }
                    }
                }
                if (array_key_exists('action', $update['update']['message'])) {
                    switch ($update['update']['message']['action']['_']) {
                    case 'messageActionChatAddUser':
                        if ($update['update']['message']['out'] == false) {
                            $user_id = $update
                            ['update']['message']['action']['users'][0];
                            $channelType = $update
                            ['update']['message']['to_id']['_'];
                            if ($channelType == 'peerChannel'
                            ) {
                                $title = $MadelineProto->get_info(
                                    -100 . $update
                                    ['update']['message']['to_id']['channel_id']
                                )
                                        ['Chat']['title'];
                                        $id = catch_id(
                                            $update,
                                            $MadelineProto,
                                            $user_id
                                        );
                                if ($id[0]) {
                                        $username = $id[2];
                                }
                                        $ch_id = -100 .
                                        $update['update']['message']['to_id']
                                        ['channel_id'];
                                        $mention = $MadelineProto->get_info(
                                            $update
                                            ['update']['message']['action']['users']
                                            [0]
                                        )
                                        ['bot_api_id'];
                                        $peer = $MadelineProto->get_info(
                                            $update['update']
                                            ['message']['to_id']
                                        )['InputPeer'];
                                if (!file_exists('banlist.json')) {
                                        $json_data = [];
                                        $json_data[$ch_id] = [];
                                        file_put_contents(
                                            'banlist.json',
                                            json_encode($json_data)
                                        );
                                }
                                        $file = file_get_contents("banlist.json");
                                        $banlist = json_decode($file, true);
                                if (array_key_exists($ch_id, $banlist)) {
                                    if (in_array($mention, $banlist[$ch_id])) {
                                        $message = "NO! They are NOT allowed here ";
                                        $kick = $MadelineProto->
                                        channels->kickFromChannel(
                                            ['channel' => $peer,
                                            'user_id' => $mention,
                                            'kicked' => true]
                                        );
                                        $sentMessage = $MadelineProto->
                                        messages->sendMessage(
                                            ['peer' => $peer,
                                            'message' => $message]
                                        );
                                        if (isset($kick)) {
                                            \danog\MadelineProto\Logger::log($kick);
                                        }
                                        \danog\MadelineProto\Logger::log(
                                            $sentMessage
                                        );
                                        break 2;
                                    }
                                }
                                        $bot_id = $MadelineProto->
                                        API->datacenter->authorization
                                        ['user']['id'];
                                if ($mention !== $bot_id) {
                                        $message = "Hi " .
                                        $username .
                                        ", welcome to " .
                                        $title;
                                        $sentMessage = $MadelineProto->
                                        messages->sendMessage(
                                            ['peer' => $peer,
                                            'message' => $message,
                                            'entities' => [[
                                            '_' => 'inputMessageEntityMentionName',
                                            'offset' => 3,
                                            'length' => strlen($username),
                                            'user_id' => $mention]]]
                                        );
                                        \danog\MadelineProto\Logger::log(
                                            $sentMessage
                                        );
                                } else {
                                        $info = $MadelineProto->get_info(
                                            $update
                                            ['update']['message']['to_id']
                                        );
                                        $adminid = $MadelineProto->get_info(
                                            getenv('TEST_USERNAME')
                                        )['bot_api_id'];
                                        $admins = cache_get_chat_info(
                                            $update,
                                            $MadelineProto
                                        );
                                    foreach (
                                        $admins['participants'] as $key) {
                                        if (array_key_exists('user', $key)) {
                                            $id = $key['user']['id'];
                                            if ($adminid !== $id) {
                                                $master_present = 'false';
                                            } else {
                                                $master_present = 'true';
                                                break;
                                            }
                                        }
                                    }

                                    if ($master_present == 'false') {
                                        $leave = $MadelineProto->
                                        channels->leaveChannel(
                                            ['channel' => $info['bot_api_id']]
                                        );
                                        \danog\MadelineProto\Logger::log($leave);
                                    }
                                }
                            }
                        }
                        break;
                    case 'messageActionChatJoinedByLink':
                        if ($update['update']['message']['out'] == false) {
                            $user_id = $update['update']['message']['from_id'];
                            $channelType = $update
                            ['update']['message']['to_id']['_'];
                            if ($channelType == 'peerChannel'
                            ) {
                                    $title = $MadelineProto->get_info(
                                        -100 . $update
                                        ['update']['message']['to_id']['channel_id']
                                    )
                                    ['Chat']['title'];
                                    $id = catch_id(
                                        $update,
                                        $MadelineProto,
                                        $user_id
                                    );
                                if ($id[0]) {
                                        $username = $id[2];
                                }
                                    $ch_id = -100 .
                                    $update['update']['message']['to_id']
                                    ['channel_id'];
                                    $mention = $MadelineProto->get_info(
                                        $update
                                        ['update']['message']['from_id']
                                    )
                                    ['bot_api_id'];
                                    $peer = $MadelineProto->get_info(
                                        $update['update']
                                        ['message']['to_id']
                                    )['InputPeer'];
                                if (!file_exists('banlist.json')) {
                                        $json_data = [];
                                        $json_data[$ch_id] = [];
                                        file_put_contents(
                                            'banlist.json',
                                            json_encode($json_data)
                                        );
                                }
                                    $file = file_get_contents("banlist.json");
                                    $banlist = json_decode($file, true);
                                if (array_key_exists($ch_id, $banlist)) {
                                    if (in_array($mention, $banlist[$ch_id])) {
                                        $message = "NO! They are NOT allowed here ";
                                        $kick = $MadelineProto->
                                        channels->kickFromChannel(
                                            ['channel' => $peer,
                                            'user_id' => $mention,
                                            'kicked' => true]
                                        );
                                        $sentMessage = $MadelineProto->
                                        messages->sendMessage(
                                            ['peer' => $peer,
                                            'message' => $message]
                                        );
                                        if (isset($kick)) {
                                            \danog\MadelineProto\Logger::log($kick);
                                        }
                                        \danog\MadelineProto\Logger::log(
                                            $sentMessage
                                        );
                                        break 2;
                                    }
                                }
                                    $bot_id = $MadelineProto->
                                    API->datacenter->authorization['user']['id'];
                                if ($mention !== $bot_id) {
                                        $message = "Hi " .
                                        $username .
                                        ", welcome to " .
                                        $title;
                                        $sentMessage = $MadelineProto->
                                        messages->sendMessage(
                                            ['peer' => $peer,
                                            'message' => $message,
                                            'entities' => [
                                            ['_' => 'inputMessageEntityMentionName',
                                            'offset' => 3,
                                            'length' => strlen($username),
                                            'user_id' => $mention]]]
                                        );
                                        \danog\MadelineProto\Logger::log(
                                            $sentMessage
                                        );
                                } else {
                                        $info = $MadelineProto->get_info(
                                            $update
                                            ['update']['message']['to_id']
                                        );
                                        $adminid = $MadelineProto->get_info(
                                            getenv('TEST_USERNAME')
                                        )['bot_api_id'];
                                        $admins = cache_get_chat_info(
                                            $update,
                                            $MadelineProto
                                        );
                                    foreach (
                                        $admins['participants'] as $key) {
                                        if (array_key_exists('user', $key)) {
                                            $id = $key['user']['id'];
                                            if ($adminid !== $id) {
                                                $master_present = 'false';
                                            } else {
                                                $master_present = 'true';
                                                break;
                                            }
                                        }
                                    }

                                    if ($master_present == 'false') {
                                        $leave = $MadelineProto->
                                        channels->leaveChannel(
                                            ['channel' => $info['bot_api_id']]
                                        );
                                        \danog\MadelineProto\Logger::log($leave);
                                    }
                                }
                            }
                        }
                        break;
                    case 'messageActionChatDeleteUser':
                        if ($update['update']['message']['out'] == false) {
                            $channelType = $update
                            ['update']['message']['to_id']['_'];
                            if ($channelType == 'peerChannel'
                            ) {
                                    $mention = $MadelineProto->get_info(
                                        $update
                                        ['update']['message']['action']['user_id']
                                    )
                                    ['bot_api_id'];
                                    $bot_id = $MadelineProto->
                                    API->datacenter->authorization['user']['id'];
                                if ($mention !== $bot_id) {
                                    $user_info = $MadelineProto->get_info(
                                        $update
                                        ['update']['message']['action']['user_id']
                                    )['User'];
                                            $title = $MadelineProto->get_info(
                                                -100 . $update
                                                ['update']['message']['to_id']
                                                ['channel_id']
                                            )
                                            ['Chat']['title'];
                                            $info = $MadelineProto->get_info(
                                                $update
                                                ['update']['message']['to_id']
                                            );
                                    if (array_key_exists('username', $user_info)
                                    ) {
                                        $username = $user_info['username'];
                                    } else {
                                        $username = $user_info['first_name'];
                                    }
                                            $adminid = $MadelineProto->get_info(
                                                getenv('TEST_USERNAME')
                                            )['user_id'];
                                    if ($mention == $adminid) {
                                        $leave = $MadelineProto->
                                        channels->leaveChannel(
                                            ['channel' => $info['bot_api_id']]
                                        );
                                        \danog\MadelineProto\Logger::log($leave);
                                    } else {
                                                $peer = $MadelineProto->get_info(
                                                    $update
                                                    ['update']['message']['to_id']
                                                )['InputPeer'];
                                                $message = "Goodbye " .
                                                $username .
                                                " :(((((";
                                                $sentMessage = $MadelineProto->
                                                messages->sendMessage(
                                                    ['peer' => $peer,
                                                    'message' => $message,
                                                    'entities'
                                                    => [
                                                    ['_' =>
                                                    'inputMessageEntityMentionName',
                                                    'offset' => 8,
                                                    'length' => strlen($username),
                                                    'user_id' => $mention]]]
                                                );
                                                \danog\MadelineProto\Logger::log(
                                                    $sentMessage
                                                );
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
        }
    }
    \danog\MadelineProto\Serialization::serialize(
        'session.madeline',
        $MadelineProto
    ).PHP_EOL;
}
