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
class Exec extends Thread
{
    public function __construct($command)
    {
        $this->command = $command;
    }
    public function run()
    {
        $this->command;
    }
}

function check_locked($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $msg_id = $update['update']['message']['id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (!from_admin_mod($update, $MadelineProto)) {
                $msg_ = $update["update"]["message"];
                if (array_key_exists("media", $msg_)) {
                    switch ($msg_["media"]["_"]) {
                    case 'messageMediaPhoto':
                        $type = "photo";
                        break;
                    case 'messageMediaVideo':
                        $type = "video";
                        break;
                    case 'messageMediaAudio':
                        $type = "audio";
                        break;
                    case 'messageMediaGeo':
                        $type = "geo";
                        break;
                    case 'messageMediaContact':
                        $type = "contact";
                        break;
                    case 'messageMediaDocument':
                        foreach ($msg_["media"]["document"]["attributes"] as $key) {
                            switch ($key["_"]) {
                            case 'documentAttributeSticker':
                                $type = "sticker";
                                break 3;
                            case 'documentAttributeAnimated':
                                $type = "gif";
                                break 3;
                            case 'documentAttributeVideo':
                                $type = "video";
                                break 3;
                            case 'documentAttributeAudio':
                                $type = "audio";
                                break 3;
                            }
                        }
                        $type = "document";
                        break;
                    }
                    if (!empty($type)) {
                        if (!file_exists('locked.json')) {
                            $json_data = [];
                            $json_data[$ch_id] = [];
                            file_put_contents(
                                'locked.json',
                                json_encode($json_data)
                            );
                        }
                        $file = file_get_contents("locked.json");
                        $locked = json_decode($file, true);
                        if (array_key_exists($ch_id, $locked)) {
                            if (in_array($type, $locked[$ch_id])) {
                                $delete = $MadelineProto->
                                channels->deleteMessages(
                                    ['channel' => $peer,
                                    'id' => [$msg_id]]
                                );
                                // \danog\MadelineProto\Logger::log($delete);
                                $thred = new Exec($delete);
                                $thred->start();
                                $thred->join();
                            }
                        }
                    }
                }
            }
        }
    }
}


function check_flood($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $msg_id = $update['update']['message']['id'];
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        check_json_array('locked.json', $ch_id);
        $file = file_get_contents("locked.json");
        $locked = json_decode($file, true);
        if (array_key_exists($ch_id, $locked)) {
            if (in_array('flood', $locked[$ch_id])) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_admin_mod($update, $MadelineProto)) {
                        global $flooder;
                        $flooder['num'] = 0;
                        $flooder['user'] = $fromid;
                    }
                    if (!empty($GLOBALS['flooder'])) {
                        $flooder = $GLOBALS['flooder'];
                        if ($fromid == $flooder['user']) {
                            $id = catch_id(
                                $update,
                                $MadelineProto,
                                $fromid
                            );
                            if ($id[0]) {
                                $username = $id[2];
                            }
                            $GLOBALS['flooder']['num'] = $flooder['num'] + 1;
                            $num = $GLOBALS['flooder']['num'];
                            if ($num >= $locked[$ch_id]['floodlimit']) {
                                if (!from_admin_mod($update, $MadelineProto)) {
                                    $kick = $MadelineProto->
                                    channels->kickFromChannel(
                                        ['channel' => $peer,
                                        'user_id' => $fromid,
                                        'kicked' => true]
                                    );
                                    $message = "Flooding is not allowed here ".
                                    $username;
                                    $mention = [[
                                        '_' => 'inputMessageEntityMentionName',
                                        'offset' => 29,
                                        'length' => strlen($username),
                                        'user_id' => $fromid]];
                                    if (isset($message)) {
                                        $sentMessage = $MadelineProto->
                                        messages->sendMessage(
                                            ['peer' => $peer,
                                            'reply_to_msg_id' => $msg_id,
                                            'message' => $message]
                                        );
                                    }
                                    if (isset($kick)) {
                                        \danog\MadelineProto\Logger::log($kick);
                                    }
                                    if (isset($sentMessage)) {
                                        \danog\MadelineProto\Logger::log(
                                            $sentMessage
                                        );
                                    }
                                    unset($GLOBALS['flooder']);
                                }
                            }
                        } else {
                            $GLOBALS['flooder']['user'] = $fromid;
                        }
                    } else {
                        global $flooder;
                        $flooder = [];
                        $flooder['user'] = $fromid;
                        $flooder['num'] = 0;
                    }
                }
            }
        }
    }
}
