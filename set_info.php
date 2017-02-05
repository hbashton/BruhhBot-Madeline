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
function set_chat_photo($update, $MadelineProto)
{
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(
            -100 . $update['update']['message']
            ['to_id']['channel_id']
        )['Chat']['title'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $from_id = $MadelineProto->get_info(
            $update
            ['update']['message']['from_id']
        )['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod(
                $update,
                $MadelineProto,
                "Only mods can use me to set this chat's photo!",
                true
            )
            ) {
                if (isset($GLOBALS['from_user_chat_photo'])) {
                    if ($GLOBALS['from_user_chat_photo'] == $from_id) {
                        if (array_key_exists(
                            "media",
                            $update['update']['message']
                        )
                        ) {
                            $mediatype = ['update']['message']['media']['_'];
                            if ($mediatype == "messageMediaPhoto") {
                                $message = "Thanks! I've updated the photo for ".
                                $title;
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
                                $changePhoto = $MadelineProto->
                                channels->editPhoto(
                                    ['channel' => $ch_id,
                                    'photo' => $inputChatPhoto]
                                );
                                \danog\MadelineProto\Logger::log($changePhoto);
                                unset($GLOBALS['from_user_chat_photo']);
                            } else {
                                $message = "The message you sent was not a photo! ".
                                "Sorry, but the chat photo was not changed";
                                unset($GLOBALS['from_user_chat_photo']);
                            }
                        } else {
                            $message = "The message you sent was not a photo! ".
                            "Sorry, but the chat photo was not changed";
                            unset($GLOBALS['from_user_chat_photo']);
                        }
                    }
                } else {
                    global $from_user_chat_photo;
                    $from_user_chat_photo = $MadelineProto->get_info(
                        $update
                        ['update']['message']['from_id']
                    )['bot_api_id'];
                    $message = "Just send the new photo and I'll get right to ".
                    "changing it!";
                }
            }
        }
        if (isset($message)) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message]
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function set_chat_title($update, $MadelineProto, $msg_str)
{
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(
            -100 . $update['update']['message']
            ['to_id']['channel_id']
        )['Chat']['title'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $from_id = $MadelineProto->get_info(
            $update
            ['update']['message']['from_id']
        )['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod(
                $update,
                $MadelineProto,
                "Only mods can change this chat's name!",
                true
            )
            ) {
                if (!empty($msg_str)) {
                    $editTitle = $MadelineProto->channels->editTitle(
                        ['channel' => $ch_id, 'title' => $msg_str ]
                    );
                    \danog\MadelineProto\Logger::log($editTitle);

                    $message = "Chat Title successfully changed to $msg_str";
                } else {
                    $message = "You can't make the title empty silly";
                }
            }
        }
        if (isset($message)) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                ['peer' => $peer, 'reply_to_msg_id' =>
                $msg_id, 'message' => $message]
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
