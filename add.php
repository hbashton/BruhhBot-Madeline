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



function add_group($update, $MadelineProto)
{
    $uMadelineProto = $MadelineProto->uMadelineProto;
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['add_group']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
                ];
            if (from_admin($update, $MadelineProto, $mods, true)) {
                check_json_array('chatlist.json', $ch_id, false);
                $file = file_get_contents('chatlist.json');
                $chatlist = json_decode($file, true);
                if (!in_array($ch_id, $chatlist)) {
                    array_push($chatlist, $ch_id);
                    file_put_contents('chatlist.json', json_encode($chatlist));
                    $str = $MadelineProto->responses['add_group']['added'];
                    $repl = ['title' => $title];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $str = $MadelineProto->responses['add_group']['already'];
                    $repl = ['title' => $title];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
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
}

function rm_group($update, $MadelineProto)
{
    $uMadelineProto = $MadelineProto->uMadelineProto;
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['rm_group']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
                ];
            if (from_admin($update, $MadelineProto, $mods, true)) {
                check_json_array('chatlist.json', $ch_id, false);
                $file = file_get_contents('chatlist.json');
                $chatlist = json_decode($file, true);
                if (in_array($ch_id, $chatlist)) {
                    if (($key = array_search(
                        $ch_id,
                        $chatlist
                    )) !== false
                    ) {
                        unset($chatlist[$key]);
                    }
                    file_put_contents('chatlist.json', json_encode($chatlist));
                    $str = $MadelineProto->responses['rm_group']['removed'];
                    $repl = ['title' => $title];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $str = $MadelineProto->responses['rm_group']['not_there'];
                    $repl = ['title' => $title];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
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
}
