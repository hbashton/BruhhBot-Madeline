#!/usr/bin/env php
<?php

function set_chat_photo($update, $MadelineProto) {
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(-100 . $update['update']['message']
        ['to_id']['channel_id'])['Chat']['title'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $from_id = $MadelineProto->get_info($update
        ['update']['message']['from_id'])['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto)) {
                if (isset($GLOBALS['from_user_chat_photo'])) {
                    if ($GLOBALS['from_user_chat_photo'] == $from_id) {
                        if (array_key_exists("media", $update['update']['message'])) {
                            if ($update['update']['message']['media']['_'] == "messageMediaPhoto") {
                                $message = "Thanks! I've updated the photo for $title";
                                $hash = $update['update']['message']['media']['photo']['access_hash'];
                                $id = $update['update']['message']['media']['photo']['id'];
                                $inputPhoto = ['_' => 'inputPhoto', 'id' => $id, 'access_hash' => $hash];
                                $inputChatPhoto = ['_' => 'inputChatPhoto', 'id' => $inputPhoto];
                                $changePhoto = $MadelineProto->channels->editPhoto(
                                ['channel' => $ch_id, 'photo' => $inputChatPhoto ]);
                                \danog\MadelineProto\Logger::log($changePhoto);
                                unset($GLOBALS['from_user_chat_photo']);
                            } else {
                                $message =
                                'The message you sent was not a photo! Sorry, but the chat photo was not changed';
                                unset($GLOBALS['from_user_chat_photo']);
                            }
                        } else {
                            $message =
                            'The message you sent was not a photo! Sorry, but the chat photo was not changed';
                            unset($GLOBALS['from_user_chat_photo']);
                        }
                    }
                } else {
                    global $from_user_chat_photo;
                    $from_user_chat_photo = $MadelineProto->get_info($update
                    ['update']['message']['from_id'])['bot_api_id'];
                    $message = "Just send the new photo and I'll get right to changing it!";
                }
            } else {
                $message = "Only mods can use me to set this chat's photo";
            }
        } else {
            $message = "I have to be an admin for this to work";
        }
        if (isset($message)) {
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]);
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function set_chat_title($update, $MadelineProto, $msg_str) {
    $msg_id = $update['update']['message']['id'];
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $title = $MadelineProto->get_info(-100 . $update['update']['message']
        ['to_id']['channel_id'])['Chat']['title'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $from_id = $MadelineProto->get_info($update
        ['update']['message']['from_id'])['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_admin_mod($update, $MadelineProto)) {
                if (!empty($msg_str)) {
                    $editTitle = $MadelineProto->channels->editTitle(
                        ['channel' => $ch_id, 'title' => $msg_str ]);
                    \danog\MadelineProto\Logger::log($editTitle);

                    $message = "Chat Title successfully changed to $msg_str";
                } else {
                    $message = "You can't make the title empty silly";
                }
            } else {
                $message = "Only mods can use me to set this chat's photo";
            }
        } else {
            $message = "I have to be an admin for this to work";
        }
        if (isset($message)) {
            $sentMessage = $MadelineProto->messages->sendMessage
            (['peer' => $peer, 'reply_to_msg_id' =>
            $msg_id, 'message' => $message]);
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}