#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2016-2017 Hunter Ashton
 * This file is part of BruhhBot.
 * BruhhBot is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * BruhhBot is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */




require 'vendor/autoload.php';
require 'vendor/rmccue/requests/library/Requests.php';
require 'vendor/spatie/emoji/src/Emoji.php';
require_once 'add.php';
require_once 'arabic.php';
require_once 'banhammer.php';
require_once 'cache.php';
require_once 'callback.php';
require_once 'callback_handlers.php';
require_once 'check_msg.php';
require_once 'data_parse.php';
require_once 'filter.php';
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

ini_set('memory_limit', '-1'); // fix errors
if (file_exists('bot.madeline')) {
    try {
        $MadelineProto = \danog\MadelineProto\Serialization::deserialize('bot.madeline');
    } catch (Exception $e) {
    }
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

$settings = json_decode(getenv('MTPROTO_SETTINGS'), true);
$settings['msg_array_limit'] = [
'incoming' => 600,
'outgoing' => 600,
];

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
if (file_exists('custom_responses.json')) {
    try {
        $MadelineProto->responses = json_decode(file_get_contents('custom_responses.json'), true);
    } catch (Exception $e) {
    }
} else {
    $MadelineProto->responses = json_decode(file_get_contents('responses.json'), true);
}
$MadelineProto->hints = json_decode(file_get_contents('hints.json'), true);
$MadelineProto->engine = new StringTemplate\Engine();
$MadelineProto->flooder = [];
$MadelineProto->cache = [];
$MadelineProto->cached_full = [];
$MadelineProto->cached_user = [];
$MadelineProto->cached_data = [];
$MadelineProto->bot_api_id = $MadelineProto->get_info(getenv('BOT_API_USERNAME'))['bot_api_id'];

//var_dump($MadelineProto->get_pwr_chat('@pwrtelegramgroup'));
Requests::register_autoloader();

$offset = 0;
$offset_user = 0;
while (true) {
    try {
        $updates = $MadelineProto->get_updates(
            ['offset' => $offset,
            'limit'   => 50000, 'timeout' => 0, ]
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
        $updates = $MadelineProto->get_updates(
            ['offset' => $offset,
            'limit'   => 50000, 'timeout' => 0, ]
        );
        if (file_exists('custom_responses.json')) {
            try {
                $MadelineProto->responses = json_decode(file_get_contents('custom_responses.json'), true);
            } catch (Exception $e) {
            }
        } else {
            $MadelineProto->responses = json_decode(file_get_contents('responses.json'), true);
        }
        $MadelineProto->hints = json_decode(file_get_contents('hints.json'), true);
        $MadelineProto->engine = new StringTemplate\Engine();
        $MadelineProto->flooder = [];
        $MadelineProto->cache = [];
        $MadelineProto->cached_full = [];
        $MadelineProto->cached_user = [];
        $MadelineProto->cached_data = [];
        $MadelineProto->bot_api_id = $MadelineProto->get_info(getenv('BOT_API_USERNAME'))['bot_api_id'];
    }
    if (end($updates)) {
        $offset = end($updates)['update_id'] + 1;
        BotAPIUpdates($updates, $MadelineProto);
    }
}
