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

class check_locked extends Threaded
{
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto)
    {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run()
    {
        require_once 'require_exceptions.php';
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        $uMadelineProto = $MadelineProto->API->uMadelineProto;
        if (bot_present($update, $MadelineProto, true)) {
            if (is_supergroup($update, $MadelineProto)) {
                $chat = parse_chat_data($update, $MadelineProto);
                $peer = $chat['peer'];
                $ch_id = $chat['id'];
                $msg_id = $update['update']['message']['id'];
                if (!from_admin_mod($update, $MadelineProto) or are_mods_restricted($ch_id)) {
                    $mod = false;
                } else {
                    $mod = true;
                }
                if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && !$mod) {
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
                                    break;
                                case 'documentAttributeAnimated':
                                    $type = "gif";
                                    break;
                                case 'documentAttributeVideo':
                                    $type = "video";
                                    break;
                                case 'documentAttributeAudio':
                                    $type = "audio";
                                    break;
                                }
                            }
                            if (empty($type)) {
                                $type = "document";
                            }
                            break;
                        }
                        if (!empty($type)) {
                            check_json_array('locked.json', $ch_id);
                            $file = file_get_contents("locked.json");
                            $locked = json_decode($file, true);
                            if (isset($locked[$ch_id])) {
                                if (in_array($type, $locked[$ch_id])) {
                                    try {
                                        $delete = $uMadelineProto->
                                        channels->deleteMessages(
                                            ['channel' => $peer,
                                            'id' => [$msg_id]]
                                        );
                                    } catch (Exception $e) {}
                                }
                            }
                        }
                    } else {
                        if (array_key_exists('message', $update['update']['message'])) {
                            if ($update['update']['message']['message'] !== '') {
                                if (is_arabic($update['update']['message']['message'])) {
                                    check_json_array('locked.json', $ch_id);
                                    $file = file_get_contents("locked.json");
                                    $locked = json_decode($file, true);
                                    if (isset($locked[$ch_id])) {
                                        if (in_array('arabic', $locked[$ch_id])) {
                                            $delete = $uMadelineProto->
                                            channels->deleteMessages(
                                                ['channel' => $peer,
                                                'id' => [$msg_id]]
                                            );
                                        }
                                    }
                                }
                                if (!check_utf8($update['update']['message']['message'])) {
                                    check_json_array('locked.json', $ch_id);
                                    $file = file_get_contents("locked.json");
                                    $locked = json_decode($file, true);
                                    if (isset($locked[$ch_id])) {
                                        if (in_array('utf', $locked[$ch_id])) {
                                            $delete = $uMadelineProto->
                                            channels->deleteMessages(
                                                ['channel' => $peer,
                                                'id' => [$msg_id]]
                                            );
                                        }
                                    }
                                }
                                if (check_for_links($update, $MadelineProto)) {
                                    check_json_array('locked.json', $ch_id);
                                    $file = file_get_contents("locked.json");
                                    $locked = json_decode($file, true);
                                    if (isset($locked[$ch_id])) {
                                        if (in_array('links', $locked[$ch_id])) {
                                            $delete = $uMadelineProto->
                                            channels->deleteMessages(
                                                ['channel' => $peer,
                                                'id' => [$msg_id]]
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

class check_flood extends Threaded
{
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto)
    {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run()
    {
        require_once 'require_exceptions.php';
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        $uMadelineProto = $MadelineProto->API->uMadelineProto;
        if (bot_present($update, $MadelineProto, true)) {
            if (is_supergroup($update, $MadelineProto)) {
                try {
                    $chat = parse_chat_data($update, $MadelineProto);
                    $peer = $chat['peer'];
                    $ch_id = $chat['id'];
                    $title = htmlentities($chat['title']);
                    $msg_id = $update['update']['message']['id'];
                    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
                    check_json_array('locked.json', $ch_id);
                    $file = file_get_contents("locked.json");
                    $locked = json_decode($file, true);
                    if (is_moderated($ch_id)) {
                        if (isset($locked[$ch_id])) {
                            if (in_array('flood', $locked[$ch_id])) {
                                if (is_bot_admin($update, $MadelineProto)) {
                                    if (from_admin_mod($update, $MadelineProto)) {
                                        $MadelineProto->flooder['num'] = 0;
                                        $MadelineProto->flooder['user'] = $fromid;
                                    }
                                    if (!empty($MadelineProto->flooder)) {
                                        if ($fromid == $MadelineProto->flooder['user']) {
                                            $id = catch_id(
                                                $update,
                                                $MadelineProto,
                                                $fromid
                                            );
                                            if ($id[0]) {
                                                $username = $id[2];
                                            }
                                            $MadelineProto->flooder['num']++;
                                            if ($MadelineProto->flooder['num'] >= $locked[$ch_id]['floodlimit']) {
                                                if (!from_admin_mod($update, $MadelineProto)) {
                                                    $kick = $uMadelineProto->
                                                    channels->kickFromChannel(
                                                        ['channel' => $peer,
                                                        'user_id' => $fromid,
                                                        'kicked' => true]
                                                    );
                                                    $mention = html_mention($username, $fromid);
                                                    $str = $MadelineProto->responses['check_flood']['kick'];
                                                    $repl = array(
                                                        "mention" => $mention
                                                    );
                                                    $message = $MadelineProto->engine->render($str, $repl);
                                                    $alert = "<code>$username was kicked for flooding in $title</code>";
                                                    if (isset($message)) {
                                                        $sentMessage = $MadelineProto->
                                                        messages->sendMessage(
                                                            ['peer' => $peer,
                                                            'reply_to_msg_id' => $msg_id,
                                                            'message' => $message,
                                                            'parse_mode' => 'html']
                                                        );
                                                    }
                                                    if (isset($kick)) {
                                                        \danog\MadelineProto\Logger::log($kick);
                                                    }
                                                    alert_moderators($MadelineProto, $ch_id, $alert);
                                                    if (isset($sentMessage)) {
                                                        \danog\MadelineProto\Logger::log(
                                                            $sentMessage
                                                        );
                                                    }
                                                    $MadelineProto->flooder = [];
                                                }
                                            }
                                        } else {
                                            $MadelineProto->flooder['user'] = $fromid;
                                            $MadelineProto->flooder['num'] = 0;
                                        }
                                    } else {
                                        $MadelineProto->flooder = [];
                                        $MadelineProto->flooder['user'] = $fromid;
                                        $MadelineProto->flooder['num'] = 0;
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {}
            }
        }
    }
}

class check_locked_user extends Threaded
{
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto)
    {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run()
    {
        require_once 'require_exceptions.php';
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        if (bot_present($update, $MadelineProto, true, false, true)) {
            if (is_supergroup($update, $MadelineProto)) {
                $chat = parse_chat_data($update, $MadelineProto);
                $peer = $chat['peer'];
                $ch_id = $chat['id'];
                $msg_id = $update['update']['message']['id'];
                if (is_moderated($ch_id) && is_bot_admin($update, $MadelineProto) && !from_admin_mod($update, $MadelineProto)) {
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
                                    break;
                                case 'documentAttributeAnimated':
                                    $type = "gif";
                                    break;
                                case 'documentAttributeVideo':
                                    $type = "video";
                                    break;
                                case 'documentAttributeAudio':
                                    $type = "audio";
                                    break;
                                }
                            }
                            if (empty($type)) {
                                $type = "document";
                            }
                            break;
                        }
                        if (!empty($type)) {
                            check_json_array('locked.json', $ch_id);
                            $file = file_get_contents("locked.json");
                            $locked = json_decode($file, true);
                            if (isset($locked[$ch_id])) {
                                if (in_array($type, $locked[$ch_id])) {
                                    $delete = $MadelineProto->
                                    channels->deleteMessages(
                                        ['channel' => $peer,
                                        'id' => [$msg_id]]
                                    );
                                }
                            }
                        }
                    } else {
                        if (array_key_exists('message', $update['update']['message'])) {
                            if ($update['update']['message']['message'] !== '') {
                                if (is_arabic($update['update']['message']['message'])) {
                                    check_json_array('locked.json', $ch_id);
                                    $file = file_get_contents("locked.json");
                                    $locked = json_decode($file, true);
                                    if (isset($locked[$ch_id])) {
                                        if (in_array('arabic', $locked[$ch_id])) {
                                            $delete = $MadelineProto->
                                            channels->deleteMessages(
                                                ['channel' => $peer,
                                                'id' => [$msg_id]]
                                            );
                                        }
                                    }
                                }
                                if (!check_utf8($update['update']['message']['message'])) {
                                    check_json_array('locked.json', $ch_id);
                                    $file = file_get_contents("locked.json");
                                    $locked = json_decode($file, true);
                                    if (isset($locked[$ch_id])) {
                                        if (in_array('utf', $locked[$ch_id])) {
                                            $delete = $MadelineProto->
                                            channels->deleteMessages(
                                                ['channel' => $peer,
                                                'id' => [$msg_id]]
                                            );
                                        }
                                    }
                                }
                                if (check_for_links($update, $MadelineProto)) {
                                    check_json_array('locked.json', $ch_id);
                                    $file = file_get_contents("locked.json");
                                    $locked = json_decode($file, true);
                                    if (isset($locked[$ch_id])) {
                                        if (in_array('links', $locked[$ch_id])) {
                                            $delete = $MadelineProto->
                                            channels->deleteMessages(
                                                ['channel' => $peer,
                                                'id' => [$msg_id]]
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

class check_flood_user extends Threaded
{
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto)
    {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run()
    {
        require_once 'require_exceptions.php';
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        if (bot_present($update, $MadelineProto, true, false, true)) {
            if (is_supergroup($update, $MadelineProto)) {
                try {
                    $chat = parse_chat_data($update, $MadelineProto);
                    $peer = $chat['peer'];
                    $ch_id = $chat['id'];
                    $msg_id = $update['update']['message']['id'];
                    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
                    check_json_array('locked.json', $ch_id);
                    $file = file_get_contents("locked.json");
                    $locked = json_decode($file, true);
                    if (is_moderated($ch_id)) {
                        if (isset($locked[$ch_id])) {
                            if (in_array('flood', $locked[$ch_id])) {
                                if (is_bot_admin($update, $MadelineProto)) {
                                    if (from_admin_mod($update, $MadelineProto)) {
                                        $MadelineProto->flooder['num'] = 0;
                                        $MadelineProto->flooder['user'] = $fromid;
                                    }
                                    if (!empty($MadelineProto->flooder)) {
                                        if ($fromid == $MadelineProto->flooder['user']) {
                                            $id = catch_id(
                                                $update,
                                                $MadelineProto,
                                                $fromid
                                            );
                                            if ($id[0]) {
                                                $username = $id[2];
                                            }
                                            $MadelineProto->flooder['num']++;
                                            if ($MadelineProto->flooder['num'] >= $locked[$ch_id]['floodlimit']) {
                                                if (!from_admin_mod($update, $MadelineProto)) {
                                                    $kick = $MadelineProto->
                                                    channels->kickFromChannel(
                                                        ['channel' => $peer,
                                                        'user_id' => $fromid,
                                                        'kicked' => true]
                                                    );
                                                    if (isset($kick)) {
                                                        \danog\MadelineProto\Logger::log($kick);
                                                    }
                                                    $MadelineProto->flooder = [];
                                                }
                                            }
                                        } else {
                                            $MadelineProto->flooder['user'] = $fromid;
                                            $MadelineProto->flooder['num'] = 0;
                                        }
                                    } else {
                                        $MadelineProto->flooder = [];
                                        $MadelineProto->flooder['user'] = $fromid;
                                        $MadelineProto->flooder['num'] = 0;
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {}
            }
        }
    }
}
