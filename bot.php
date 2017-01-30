#!/usr/bin/env php
<?php

require 'MadelineProto/vendor/autoload.php';
require 'vendor/autoload.php';
require 'vendor/rmccue/requests/library/Requests.php';
require 'vendor/spatie/emoji/src/Emoji.php';
require 'time.php';
require 'weather.php';
require 'supergroup.php';
$number = '+12053903877';
$MadelineProto = \danog\MadelineProto\Serialization::deserialize
('session.madeline');
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
    $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']
    ['length'] : 5) + 1);
    $authorization = $MadelineProto->complete_phone_login($code);
    \danog\MadelineProto\Logger::log($authorization);
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize
    ('session.madeline', $MadelineProto).' bytes'.PHP_EOL;

echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
$MadelineProto = \danog\MadelineProto\Serialization::deserialize
('session.madeline');
}
$offset = 0;
while (true) {
    $updates = $MadelineProto->API->get_updates(['offset' => $offset,
               'limit' => 50000, 'timeout' => 0]);
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
                $first_char = substr($update['update']['message']
                ['message'][0], 0, 1);
                if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                    $msg_str = substr( $update['update']['message']
                    ['message'], 1);
                    $msg_id = $update['update']['message']['id'];
                    $msg_arr = explode(' ',trim($msg_str));
                    switch ($msg_arr[0]) {
                        case 'time':
                        unset($msg_arr[0]);
                        $msg_str = implode(" ",$msg_arr);
                        $message = getloc($msg_str);
                        $peer = $MadelineProto->get_info($update['update']
                            ['message']['from_id'])['bot_api_id'];
                        $sentMessage = $MadelineProto->messages->sendMessage
                        (['peer' => $peer, 'message' => $message, 'entities'
                        => [['_' => 'messageEntityUnknown',
                        'offset' => 0, 'length' => strlen($message)]]]);
                        \danog\MadelineProto\Logger::log($sentMessage);
                        break 2;

                        case 'weather':
                        unset($msg_arr[0]);
                        $msg_str = implode(" ",$msg_arr);
                        #var_dump(getloc($msg_str));
                        $message = getweather($msg_str);
                        $peer = $MadelineProto->get_info($update['update']
                        ['message']['from_id'])['bot_api_id'];
                        $sentMessage = $MadelineProto->messages->sendMessage
                        (['peer' => $peer, 'message' => $message, 'entities'
                        => [['_' => 'messageEntityUnknown',
                        'offset' => 0, 'length' => strlen($message)]]]);
                        \danog\MadelineProto\Logger::log($sentMessage);
                        break 2;
                        }
                    }
                }
            }

            case 'updateNewChannelMessage':
            $res = json_encode($update, JSON_PRETTY_PRINT);
            var_dump($update);
            if (array_key_exists('message', $update['update']['message']) &&
            is_string($update['update']['message']['message']) &&
            strlen($update['update']['message']['message']) !== 0) {
                $first_char = substr($update['update']['message']['message'][0],
                0, 1);

                if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                    $msg_str = substr(
                    $update['update']['message']['message'], 1);
                    $msg_id = $update['update']['message']['id'];
                    $msg_arr = explode(' ',trim($msg_str));
                    switch ($msg_arr[0]) {
                        case 'time':
                        unset($msg_arr[0]);
                        $msg_str = implode(" ",$msg_arr);
                        $message = getloc($msg_str);
                        $peer = $MadelineProto->get_info($update['update']
                            ['message']['to_id'])['InputPeer'];
                        $sentMessage = $MadelineProto->messages->sendMessage
                        (['peer' => $peer, 'reply_to_msg_id' =>
                        $msg_id, 'message' => $message, 'entities'
                        => [['_' => 'messageEntityUnknown',
                        'offset' => 0, 'length' => strlen($message)]]]);
                        \danog\MadelineProto\Logger::log($sentMessage);
                        break;

                        case 'weather':
                        unset($msg_arr[0]);
                        $msg_str = implode(" ",$msg_arr);
                        $message = getweather($msg_str);
                        $peer = $MadelineProto->get_info($update['update']
                        ['message']['to_id'])['bot_api_id'];
                        $sentMessage = $MadelineProto->messages->sendMessage
                        (['peer' => $peer, 'message' => $message, 'entities'
                        => [['_' => 'messageEntityUnknown',
                        'offset' => 0, 'length' => strlen($message)]]]);
                        \danog\MadelineProto\Logger::log($sentMessage);
                        break;

                        case 'adminlist':
                        adminlist($update, $MadelineProto);
                        break;
                    }
                }
            }
            if (array_key_exists('action', $update['update']['message'])) {
                switch ($update['update']['message']['action']['_']) {
                    case 'messageActionChatAddUser':
                    if ($update['update']['message']['out'] == False) {
                        $user_info = $MadelineProto->get_info($update
                        ['update']['message']['action']['users'][0])['User'];
                        if ($update['update']['message']['to_id']['_'] ==
                        'peerChannel') {
                        $title = $MadelineProto->get_info(-100 . $update
                        ['update']['message']['to_id']['channel_id'])
                        ['Chat']['title'];
                        if (array_key_exists('username', $user_info)) {
                        $username = $user_info['username'];
                        } else {
                        $username = $user_info['first_name'];
                        }
                        $mention = $MadelineProto->get_info($update
                        ['update']['message']['action']['users'][0])
                        ['bot_api_id'];
                        $bot_id = $MadelineProto->API->datacenter->authorization
                        ['user']['id'];
                        if ($mention !== $bot_id) {
                            $peer = $MadelineProto->get_info($update['update']
                            ['message']['to_id'])['InputPeer'];
                            $message = "Hi " . $username . ", welcome to " .
                            $title;
                            $sentMessage = $MadelineProto->messages->sendMessage
                            (['peer' => $peer, 'message' => $message, 'entities'
                            => [['_' => 'inputMessageEntityMentionName',
                            'offset' => 3, 'length' => strlen($username),
                            'user_id' => $mention]]]);
                            \danog\MadelineProto\Logger::log($sentMessage);
                        } else {
                            $info = $MadelineProto->get_info($update
                            ['update']['message']['to_id']);
                            $adminid = $MadelineProto->get_info(getenv
                            ('TEST_USERNAME'))['bot_api_id'];
                            $get_chat_info = $MadelineProto->
                            get_pwr_chat(
                            $info['bot_api_id']);
                            foreach (
                            $get_chat_info['participants'] as $key) {
                                    $id = $key['user']['id'];
                                        if ($adminid !== $id) {
                                            $master_present = 'false';
                                        } else {
                                            $master_present = 'true';
                                            break;
                                        }
                                }

                            if ($master_present == 'false') {
                                $leave = $MadelineProto->channels->leaveChannel
                                (['channel' => $info['bot_api_id']]);
                                \danog\MadelineProto\Logger::log($leave);
                                }

                             }
                         }
                    }
                    break;
                    case 'messageActionChatDeleteUser':
                    if ($update['update']['message']['out'] == False) {
                        if ($update['update']['message']['to_id']['_'] ==
                        'peerChannel') {
                        $mention = $MadelineProto->get_info($update
                        ['update']['message']['action']['user_id'])
                        ['bot_api_id'];
                        $bot_id = $MadelineProto->API->datacenter->authorization
                        ['user']['id'];
                        if ($mention !== $bot_id) {
                        $user_info = $MadelineProto->get_info($update
                        ['update']['message']['action']['user_id'])['User'];
                        $title = $MadelineProto->get_info(-100 . $update
                        ['update']['message']['to_id']['channel_id'])
                        ['Chat']['title'];
                        $info = $MadelineProto->get_info($update
                        ['update']['message']['to_id']);
                        if (array_key_exists('username', $user_info)) {
                            $username = $user_info['username'];
                        } else {
                            $username = $user_info['first_name'];
                        }
                        $adminid = $MadelineProto->get_info(getenv
                        ('TEST_USERNAME'))['user_id'];
                            if ($mention == $adminid) {
                                $leave = $MadelineProto->channels->leaveChannel
                                (['channel' => $info['bot_api_id']]);
                                \danog\MadelineProto\Logger::log($leave);
                            } else {
                                $peer = $MadelineProto->get_info($update
                                ['update']['message']['to_id'])['InputPeer'];
                                $message = "Goodbye " . $username . " :(((((";
                                $sentMessage =
                                $MadelineProto->messages->sendMessage
                                (['peer' => $peer, 'message' => $message,
                                'entities'
                                => [['_' => 'inputMessageEntityMentionName',
                                'offset' => 8, 'length' => strlen($username),
                                'user_id' => $mention]]]);
                                \danog\MadelineProto\Logger::log($sentMessage);
                                }
                            }
                        }
                    }
                    break;
                }
            }
        }
    }
    \danog\MadelineProto\Serialization::serialize
    ('session.madeline', $MadelineProto).PHP_EOL;
}
