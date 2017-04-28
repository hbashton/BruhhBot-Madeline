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
require 'vendor/autoload.php';
require 'vendor/rmccue/requests/library/Requests.php';
require 'vendor/spatie/emoji/src/Emoji.php';
require_once 'add.php';
require_once 'arabic.php';
require_once 'banhammer.php';
require_once 'bot_api.php';
require_once 'cache.php';
require_once 'callback.php';
require_once 'callback_handlers.php';
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
require_once 'start_help.php';
require_once 'supergroup.php';
require_once 'time.php';
require_once 'threading.php';
require_once 'to_all.php';
require_once 'user_data.php';
require_once 'weather.php';
require_once 'who_functions.php';

ini_set('memory_limit', '1000M'); // fix errors
if (file_exists('session.madeline')) {
    try {
        $uMadelineProto = \danog\MadelineProto\Serialization::deserialize('session.madeline');
    } catch (Exception $e) {}
}
if (file_exists('bot.madeline')) {
    try {
        $MadelineProto = \danog\MadelineProto\Serialization::deserialize('bot.madeline');
    } catch (Exception $e) {}
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

$settings = json_decode(getenv("MTPROTO_SETTINGS"), true);

if (!isset($uMadelineProto)) {
    $uMadelineProto = new \danog\MadelineProto\API($settings);
    $checkedPhone = $uMadelineProto->auth->checkPhone(
        [
            'phone_number'     => getenv('MTPROTO_NUMBER'),
        ]
    );
    \danog\MadelineProto\Logger::log($checkedPhone);
    $sentCode = $uMadelineProto->phone_login(getenv('MTPROTO_NUMBER'));
    \danog\MadelineProto\Logger::log($sentCode);
    echo 'Enter the code you received: ';
    $code = fgets(
        STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']
        ['length'] : 5) + 1
    );
    $authorization = $uMadelineProto->complete_phone_login($code);

        \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
        if ($authorization['_'] === 'account.noPassword') {
            throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
        }
        if ($authorization['_'] === 'account.password') {
            \danog\MadelineProto\Logger::log(['2FA is enabled'], \danog\MadelineProto\Logger::NOTICE);
            $authorization = $uMadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
        }
        if ($authorization['_'] === 'account.needSignup') {
            \danog\MadelineProto\Logger::log(['Registering new user'], \danog\MadelineProto\Logger::NOTICE);
            $authorization = $uMadelineProto->complete_signup($code, readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
        }

    \danog\MadelineProto\Logger::log([$authorization]);
    echo 'Serializing MadelineProto to session.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize(
        'session.madeline',
        $uMadelineProto
    ).' bytes'.PHP_EOL;

    echo 'Deserializing MadelineProto from session.madeline...'.PHP_EOL;
    $uMadelineProto = \danog\MadelineProto\Serialization::deserialize(
        'session.madeline'
    );
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
    echo 'Serializing MadelineProto to bot.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize(
        'bot.madeline',
        $MadelineProto
    ).' bytes'.PHP_EOL;

    echo 'Deserializing MadelineProto from bot.madeline...'.PHP_EOL;
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize(
        'bot.madeline'
    );
}
if (!isset($MadelineProto)) {
    $MadelineProto = new \danog\MadelineProto\API($settings);
    $authorization = $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
    echo 'Serializing MadelineProto to bot.madeline...'.PHP_EOL;
    echo 'Wrote '.\danog\MadelineProto\Serialization::serialize(
        'bot.madeline',
        $MadelineProto
    ).' bytes'.PHP_EOL;

    echo 'Deserializing MadelineProto from bot.madeline...'.PHP_EOL;
    $MadelineProto = \danog\MadelineProto\Serialization::deserialize(
        'bot.madeline'
    );
}
$MadelineProto->responses = json_decode(file_get_contents("responses.json"), true);
$MadelineProto->hints = json_decode(file_get_contents("hints.json"), true);
$MadelineProto->engine = new StringTemplate\Engine;
$MadelineProto->flooder = [];
$MadelineProto->API->is_bot_present = [];
$MadelineProto->API->cache = [];
$MadelineProto->API->bot_id = $MadelineProto->get_info(getenv('BOT_USERNAME'))['bot_api_id'];
$MadelineProto->API->bot_api_id = $MadelineProto->get_info(getenv('BOT_API_USERNAME'))['bot_api_id'];
$MadelineProto->API->uMadelineProto = $uMadelineProto;
$MadelineProto->API->uMadelineProto->responses = json_decode(file_get_contents("responses.json"), true);
$MadelineProto->API->uMadelineProto->hints = json_decode(file_get_contents("hints.json"), true);
$MadelineProto->API->uMadelineProto->engine = new StringTemplate\Engine;
$MadelineProto->API->uMadelineProto->flooder = [];
$MadelineProto->API->uMadelineProto->API->is_bot_present = [];
$MadelineProto->API->uMadelineProto->API->bot_id = $MadelineProto->get_info(getenv('BOT_USERNAME'))['bot_api_id'];
$MadelineProto->API->uMadelineProto->API->bot_api_id = $MadelineProto->get_info(getenv('BOT_API_USERNAME'))['bot_api_id'];

//var_dump($MadelineProto->get_pwr_chat('@pwrtelegramgroup'));
Requests::register_autoloader();
$pool = new Pool(100);

$offset = 0;
$offset_user = 0;
while (true) {
    try {
        $updates = $MadelineProto->API->get_updates(
            ['offset' => $offset,
            'limit' => 50000, 'timeout' => 0]
        );
    } catch (Exception $e) {
        $MadelineProto = new \danog\MadelineProto\API($settings);
        $authorization = $MadelineProto->bot_login(getenv('BOT_TOKEN'));
        \danog\MadelineProto\Logger::log([$authorization], \danog\MadelineProto\Logger::NOTICE);
        echo 'Serializing MadelineProto to bot.madeline...'.PHP_EOL;
        echo 'Wrote '.\danog\MadelineProto\Serialization::serialize(
            'bot.madeline',
            $MadelineProto
        ).' bytes'.PHP_EOL;

        echo 'Deserializing MadelineProto from bot.madeline...'.PHP_EOL;
        $MadelineProto = \danog\MadelineProto\Serialization::deserialize(
            'bot.madeline'
        );
        $updates = $MadelineProto->API->get_updates(
            ['offset' => $offset,
            'limit' => 50000, 'timeout' => 0]
        );
    }
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        switch ($update['update']['_']) {
        case 'updateNewMessage':
            if ($dumpme) {
                var_dump($update);
            }
            if (is_peeruser($update, $MadelineProto)) {
                $pool->submit(new NewMessage($update, $MadelineProto));
            }
        break;
        case 'updateNewChannelMessage':
            if ($dumpme) {
                var_dump($update);
            }
            if (is_supergroup($update, $MadelineProto)) {
                $pool->submit(new check_locked($update, $MadelineProto));
                $pool->submit(new check_flood($update, $MadelineProto));
                $pool->submit(new NewChannelMessage($update, $MadelineProto));
                if (array_key_exists('action', $update['update']['message'])) {
                    $pool->submit(new NewChannelMessageAction($update, $MadelineProto));
                }
            }
        break;
        case 'updateBotCallbackQuery':
            if ($dumpme) {
                var_dump($update);
            }
            if (is_supergroup($update, $MadelineProto) or is_peeruser($update, $MadelineProto)) {
                $pool->submit(new BotCallbackQuery($update, $MadelineProto));
            }
        }
    }
    try {
        $updates = $uMadelineProto->API->get_updates(
            ['offset' => $offset_user,
            'limit' => 50000, 'timeout' => 0]
        );
    } catch (Exception $e) {}
    foreach ($updates as $update) {
        $offset_user = $update['update_id'] + 1;
        switch ($update['update']['_']) {
        case 'updateNewChannelMessage':
            if ($dumpme) {
                var_dump($update);
            }
            if (is_supergroup($update, $MadelineProto)) {
                if (!array_key_exists("from_id", $update['update']['message'])) break;
                try {
                    $user = cache_from_user_info($update, $uMadelineProto);
                } catch (Exception $e) {
                    break;
                }
                if (!array_key_exists("type", $user)) break;
                if ($user['type'] != "bot") {
                    $uMadelineProto->flooder['num'] = 0;
                    $uMadelineProto->flooder['user'] = $update['update']['message']['from_id'];
                    break;
                }
                if (isset($user['bot_api_id'])) {
                    if ($user['bot_api_id'] == $MadelineProto->API->bot_api_id) break;
                }
                $pool->submit(new check_locked_user($update, $uMadelineProto));
                $pool->submit(new check_flood_user($update, $uMadelineProto));
                $pool->submit(new NewChannelMessageUserBot($update, $uMadelineProto));
            }
        break;
        }
    }
    \danog\MadelineProto\Serialization::serialize('bot.madeline', $MadelineProto).PHP_EOL;
    \danog\MadelineProto\Serialization::serialize('session.madeline', $uMadelineProto).PHP_EOL;
}
