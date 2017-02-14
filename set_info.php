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

function set_chat_photo($update, $MadelineProto, $wait = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['set_chat_photo']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod(
                    $update,
                    $MadelineProto,
                    $mods,
                    $wait
                )
                ) {
                    if (isset($GLOBALS['from_user_chat_photo'])) {
                        if ($GLOBALS['from_user_chat_photo'] == $fromid) {
                            if (array_key_exists(
                                "media",
                                $update['update']['message']
                            )
                            ) {
                                $mediatype = $update['update']['message']['media']['_'];
                                if ($mediatype == "messageMediaPhoto") {
                                    $hash = $update
                                    ['update']['message']['media']['photo']
                                    ['access_hash'];
                                    $id = $update
                                    ['update']['message']['media']['photo']['id'];
                                    $inputPhoto = [
                                        '_' => 'inputPhoto',
                                        'id' => $id,
                                        'access_hash' => $hash];
                                    $inputChatPhoto = [
                                        '_' => 'inputChatPhoto',
                                        'id' => $inputPhoto];
                                    try {
                                        $changePhoto = $MadelineProto->
                                        channels->editPhoto(
                                            ['channel' => $ch_id,
                                            'photo' => $inputChatPhoto]
                                        );
                                        \danog\MadelineProto\Logger::log(
                                            $changePhoto
                                        );
                                        $str = $responses['set_chat_photo']['success'];
                                        $repl = array(
                                            "title" => $title
                                        );
                                        $message = $engine->render($str, $repl);
                                        $default['message'] = $message;

                                    } catch (Exception $e) {
                                        $message = $responses['set_chat_photo']['exception'];
                                        $default['message'] = $message;
                                    }
                                    unset($GLOBALS['from_user_chat_photo']);
                                } else {
                                    $message = $responses['set_chat_photo']['sorry'];
                                    $default['message'] = $message;
                                    unset($GLOBALS['from_user_chat_photo']);
                                }
                            } else {
                                $message = $responses['set_chat_photo']['sorry'];
                                $default['message'] = $message;
                                unset($GLOBALS['from_user_chat_photo']);
                            }
                        }
                    } else {
                        global $from_user_chat_photo;
                        $from_user_chat_photo = $fromid;
                        $message = $responses['set_chat_photo']['ready'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}

function set_chat_title($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        global $responses, $engine;
        $msg_id = $update['update']['message']['id'];
        $mods = $responses['set_chat_title']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod(
                    $update,
                    $MadelineProto,
                    $mods,
                    true
                )
                ) {
                    if ($msg) {
                        try {
                            $editTitle = $MadelineProto->channels->editTitle(
                                ['channel' => $ch_id, 'title' => $msg ]
                            );
                            \danog\MadelineProto\Logger::log($editTitle);
                            $str = $responses['set_chat_title']['success'];
                            $repl = array(
                                "msg" => $msg
                            );
                            $message = $engine->render($str, $repl);
                            $default['message'] = $message;
                        } catch (Exception $e) {
                            $message = $responses['set_chat_title']['fail'];
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $responses['set_chat_title']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (!isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
