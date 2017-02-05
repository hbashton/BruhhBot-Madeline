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
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $msg_id = $update['update']['message']['id'];
        $cont = "true";
        break;
    }
    if (isset($cont)) {
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
                        $ch_id = -100 .
                        $update['update']['message']['to_id']['channel_id'];
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
    if ($update['update']['message']['to_id']['_'] == "peerChannel") {
        $peer = $MadelineProto->get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
        $msg_id = $update['update']['message']['id'];
        $fromid = $MadelineProto->get_info(
            $update['update']
            ['message']['from_id']
        )['bot_api_id'];
        if (!file_exists('locked.json')) {
            $json_data = [];
            $json_data[$ch_id] = [];
            file_put_contents('locked.json', json_encode($json_data));
        }
        $file = file_get_contents("locked.json");
        $locked = json_decode($file, true);
        if (array_key_exists($ch_id, $locked)) {
            if (in_array('flood', $locked[$ch_id])) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (!empty($GLOBALS['flooder'])) {
                        $flooder = $GLOBALS['flooder'];
                        if ($fromid == $flooder['user']) {
                            $GLOBALS['flooder']['num'] = $flooder['num'] + 1;
                            $num = $GLOBALS['flooder']['num'];
                            if ($num >= $locked[$ch_id]['floodlimit']) {
                                if (!from_admin_mod($update, $MadelineProto)) {
                                    $kick = $MadelineProto->
                                    channels->kickFromChannel(
                                        ['channel' => $peer,
                                        'user_id' => $userid,
                                        'kicked' => true]
                                    );
                                    $message = "Flooding is not allowed here ".
                                    $username;
                                    $mention = [[
                                        '_' => 'inputMessageEntityMentionName', 
                                        'offset' => 29, 
                                        'length' => strlen($username), 
                                        'user_id' => $fromid]];
                                    \danog\MadelineProto\Logger::log($kick);
                                    \danog\MadelineProto\Logger::log($sentMessage);
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
