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




function set_chat_photo($update, $MadelineProto, $wait = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['set_chat_photo']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        if (is_moderated($ch_id)) {
            if (is_bot_admin($update, $MadelineProto)) {
                if (from_admin_mod(
                    $update,
                    $MadelineProto,
                    $mods,
                    $wait
                )
                ) {
                    if (isset($MadelineProto->from_user_chat_photo)) {
                        if ($MadelineProto->from_user_chat_photo == $fromid) {
                            if (array_key_exists(
                                'media',
                                $update['update']['message']
                            )
                            ) {
                                $mediatype = $update['update']['message']['media']['_'];
                                if ($mediatype == 'messageMediaPhoto') {
                                    $hash = $update['update']['message']['media']['photo']['access_hash'];
                                    $id = $update['update']['message']['media']['photo']['id'];
                                    $inputPhoto = [
                                        '_'           => 'inputPhoto',
                                        'id'          => $id,
                                        'access_hash' => $hash, ];
                                    $inputChatPhoto = [
                                        '_'  => 'inputChatPhoto',
                                        'id' => $inputPhoto, ];
                                    try {
                                        $changePhoto = $MadelineProto->
                                        channels->editPhoto(
                                            ['channel' => $ch_id,
                                            'photo'    => $inputChatPhoto, ]
                                        );
                                        \danog\MadelineProto\Logger::log(
                                            $changePhoto
                                        );
                                        $str = $MadelineProto->responses['set_chat_photo']['success'];
                                        $repl = [
                                            'title' => $title,
                                        ];
                                        $message = $MadelineProto->engine->render($str, $repl);
                                        $default['message'] = $message;
                                        $alert = "<code>$from_name changed the photo for $title</code>";
                                    } catch (Exception $e) {
                                        $message = $MadelineProto->responses['set_chat_photo']['exception']."\n".$e->getMessage();
                                        $default['message'] = $message;
                                    }
                                    unset($MadelineProto->from_user_chat_photo);
                                } else {
                                    $message = $MadelineProto->responses['set_chat_photo']['sorry'];
                                    $default['message'] = $message;
                                    unset($MadelineProto->from_user_chat_photo);
                                }
                            } else {
                                $message = $MadelineProto->responses['set_chat_photo']['sorry'];
                                $default['message'] = $message;
                                unset($MadelineProto->from_user_chat_photo);
                            }
                        }
                    } else {
                        $MadelineProto->from_user_chat_photo = $fromid;
                        $message = $MadelineProto->responses['set_chat_photo']['ready'];
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
            if (isset($alert)) {
                alert_moderators($MadelineProto, $ch_id, $alert);
                alert_moderators_forward($MadelineProto, $ch_id, $msg_id);
            }
        }
    }
}

function set_chat_title($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['set_chat_title']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
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
                                ['channel' => $ch_id, 'title' => $msg]
                            );
                            \danog\MadelineProto\Logger::log($editTitle);
                            $str = $MadelineProto->responses['set_chat_title']['success'];
                            $repl = [
                                'msg' => $msg,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                            $alert = "<code>$from_name changed the name of $title to \"$msg\"</code>";
                        } catch (Exception $e) {
                            $message = $MadelineProto->responses['set_chat_title']['fail'];
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $MadelineProto->responses['set_chat_title']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function set_chat_username($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['set_chat_title']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
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
                            if ($msg == 'clear') {
                                $msg = '';
                                $changeUsername = $MadelineProto->channels->updateUsername(
                                ['channel' => $ch_id, 'username' => $msg]
                                );
                                \danog\MadelineProto\Logger::log($changeUsername);
                                $str = $MadelineProto->responses['set_chat_username']['clear'];
                                $repl = [
                                    'title' => $title,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            } else {
                                $changeUsername = $MadelineProto->channels->updateUsername(
                                    ['channel' => $ch_id, 'username' => $msg]
                                );
                                \danog\MadelineProto\Logger::log($changeUsername);
                                $str = $MadelineProto->responses['set_chat_username']['success'];
                                $repl = [
                                    'msg' => $msg,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } catch (Exception $e) {
                            $message = $MadelineProto->responses['set_chat_username']['fail'];
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $MadelineProto->responses['set_chat_username']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}

function set_chat_about($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['set_chat_about']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
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
                        if (preg_match('/"([^"]+)"/', $msg, $m)) {
                            $msg = $m[1];
                        } else {
                            $message = $MadelineProto->responses['set_chat_about']['help'];
                            $default['message'] = $message;
                        }
                        try {
                            $editAbout = $MadelineProto->channels->editAbout(
                                ['channel' => $ch_id, 'about' => $msg]
                            );
                            \danog\MadelineProto\Logger::log($editAbout);
                            $str = $MadelineProto->responses['set_chat_about']['success'];
                            $repl = [
                                'msg' => $msg,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                            $alert = "<code>$from_name changed the description of $title to \"$msg\"</code>";
                        } catch (Exception $e) {
                            $message = $MadelineProto->responses['set_chat_about']['fail'];
                            $default['message'] = $message;
                        }
                    } else {
                        $message = $MadelineProto->responses['set_chat_about']['help'];
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}

function set_chat_rules($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['set_chat_title']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
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
                        if ($msg != 'clear') {
                            check_json_array('settings.json', $ch_id);
                            $file = file_get_contents('settings.json');
                            $settings = json_decode($file, true);
                            $settings[$ch_id]['rules'] = $msg;
                            file_put_contents('settings.json', json_encode($settings));
                            $default['message'] = "Alright, I've set the rules for $title. You can get them with /rules";
                            $alert = "<code>$from_name changed the rules in $title to:\n\"$msg\"</code>";
                        } else {
                            check_json_array('settings.json', $ch_id);
                            $file = file_get_contents('settings.json');
                            $settings = json_decode($file, true);
                            $settings[$ch_id]['rules'] = '';
                            file_put_contents('settings.json', json_encode($settings));
                            $default['message'] = "Alright, I've cleared the rules for $title. Total anarchy";
                            $alert = "<code>$from_name cleared the rules in $title</code>";
                        }
                    } else {
                        $message = "Set the rules with /setrules <code>rules</code>\nClear them with /setrules <code>clear</code>";
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}

function set_chat_welcome($update, $MadelineProto, $msg)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $mods = $MadelineProto->responses['set_chat_title']['mods'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        $from_name = catch_id($update, $MadelineProto, $fromid)[2];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
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
                        if ($msg != 'clear') {
                            check_json_array('settings.json', $ch_id);
                            $file = file_get_contents('settings.json');
                            $settings = json_decode($file, true);
                            $settings[$ch_id]['custom_welcome'] = $msg;
                            file_put_contents('settings.json', json_encode($settings));
                            $default['message'] = "I've set the welcome message for $title.";
                            $alert = "<code>$from_name set the welcome message in $title as \"$msg\"</code>";
                        } else {
                            check_json_array('settings.json', $ch_id);
                            $file = file_get_contents('settings.json');
                            $settings = json_decode($file, true);
                            if (isset($settings[$ch_id]['custom_welcome'])) {
                                unset($settings[$ch_id]['custom_welcome']);
                            }
                            file_put_contents('settings.json', json_encode($settings));
                            $default['message'] = 'Welcome set to default';
                            $alert = "<code>$from_name cleared the welcome message in $title</code>";
                        }
                    } else {
                        $message = "Set the welcome message with /welcome <code>message</code>\nClear it with /welcome <code>clear</code>\n<code>Message me help and navigate to the Welcome menu to see a list of variables";
                        $default['message'] = $message;
                    }
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
        if (isset($alert)) {
            alert_moderators($MadelineProto, $ch_id, $alert);
        }
    }
}
