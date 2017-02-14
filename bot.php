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
require_once 'add.php';
require_once 'banhammer.php';
require_once 'cache.php';
require_once 'check_msg.php';
require_once 'data_parse.php';
require_once 'id_.php';
require_once 'invite.php';
require_once 'lock.php';
require_once 'moderators.php';
require_once 'mutehammer.php';
require_once 'promote.php';
require_once 'save_get.php';
require_once 'set_info.php';
require_once 'settings.php';
require_once 'supergroup.php';
require_once 'time.php';
require_once 'threading.php';
require_once 'user_data.php';
require_once 'weather.php';
require_once 'who_functions.php';
if (file_exists('session.madeline')) {
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
    Requests::register_autoloader();
}
if (file_exists('.env')) {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
}
if (isset($argv[1])) {
    $dumpme = true;
} else {
    $dumpme = false;
}
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

if (!isset($MadelineProto)) {
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
$responses_file = file_get_contents("responses.json");
$responses = json_decode($responses_file, true);
$engine = new StringTemplate\Engine;
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
            if ($dumpme) {
                var_dump($update);
            }
            $NewMessage = new NewMessage($update, $MadelineProto);
            $NewMessage->start();
            break;

        case 'updateNewChannelMessage':
            $res = json_encode($update, JSON_PRETTY_PRINT);
            if ($dumpme) {
                var_dump($update);
            }
            $command = check_locked($update, $MadelineProto);
            $check = new Exec($command);
            $check->start();
            $command = check_flood($update, $MadelineProto);
            $check = new Exec($command);
            $check->start();
            $NewChannelMessage = new NewChannelMessage($update, $MadelineProto);
            $NewChannelMessage->start();
            if (array_key_exists('action', $update['update']['message'])) {
                $NewChannelMessageAction =
                    new NewChannelMessageAction($update, $MadelineProto);
                $NewChannelMessageAction->start();
            }
        }
    }
    \danog\MadelineProto\Serialization::serialize(
        'session.madeline',
        $MadelineProto
    ).PHP_EOL;
}
