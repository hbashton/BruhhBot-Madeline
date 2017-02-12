<?php

class NewMessage extends Thread {
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto) {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run() {
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        if (array_key_exists('message', $update['update']['message'])) {
            if ($update['update']['message']['message'] !== '') {
                $first_char = substr(
                    $update['update']['message']
                    ['message'][0], 0, 1
                );
                if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                    $msg_str = substr(
                        $update['update']['message']
                        ['message'], 1
                    );
                        $msg_id = $update['update']['message']['id'];
                        $msg_arr = explode(' ', trim($msg_str));
                        switch ($msg_arr[0]) {
                    case 'time':
                        unset($msg_arr[0]);
                        $msg_str = implode(" ", $msg_arr);
                        getloc($update, $MadelineProto, $msg_str);
                        break;

                    case 'weather':
                        unset($msg_arr[0]);
                        $msg_str = implode(" ", $msg_arr);
                        getweather($update, $MadelineProto, $msg_str);
                        break;

                    case 'id':
                        unset($msg_arr[0]);
                        $msg_str = implode(" ", $msg_arr);
                        idme($update, $MadelineProto, $msg_str);
                        break;
                        }
                }
            }
        }
    }
}

class NewChannelMessage extends Thread {
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto) {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run() {
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (array_key_exists('message', $update['update']['message'])
            && is_string($update['update']['message']['message'])
        ) {
            if (is_supergroup($update, $MadelineProto)) {
                $command = new CheckMuted($update, $MadelineProto);
                $command->start();
                $command->join();
                $chat = parse_chat_data($update, $MadelineProto);
                $peer = $chat['peer'];
                $ch_id = $chat['id'];
                check_json_array('banlist.json', $ch_id);
                $file = file_get_contents("banlist.json");
                $banlist = json_decode($file, true);
                $msg_id = $update['update']['message']['id'];
                if (is_bot_admin($update, $MadelineProto)) {
                    if (!from_admin_mod($update, $MadelineProto)) {
                        $default = array(
                            'peer' => $peer,
                            'reply_to_msg_id' => $msg_id,
                        );
                        check_json_array('gbanlist.json', false, false);
                        $file = file_get_contents("gbanlist.json");
                        $gbanlist = json_decode($file, true);
                        if (in_array($fromid, $gbanlist)) {
                            $message = "I really don't like them!";
                            $default['message'] = $message;
                            $kick = $MadelineProto->
                            channels->kickFromChannel(
                                ['channel' => $peer,
                                'user_id' => $fromid,
                                'kicked' => true]
                            );
                            $sentMessage = $MadelineProto->
                            messages->sendMessage(
                                $default
                            );
                            if (isset($kick)) {
                                \danog\MadelineProto\Logger::log($kick);
                            }
                            \danog\MadelineProto\Logger::log(
                                $sentMessage
                            );
                        }
                        if (array_key_exists($ch_id, $banlist)) {
                            if (in_array($fromid, $banlist[$ch_id])) {
                                try {
                                    $message = "NO! They are NOT allowed here!";
                                    $default['message'] = $message;
                                    $kick = $MadelineProto->
                                    channels->kickFromChannel(
                                        ['channel' => $peer,
                                        'user_id' => $fromid,
                                        'kicked' => true]
                                    );
                                    $sentMessage = $MadelineProto->
                                    messages->sendMessage(
                                        $default
                                    );
                                    if (isset($kick)) {
                                        \danog\MadelineProto\Logger::log($kick);
                                    }
                                    \danog\MadelineProto\Logger::log(
                                        $sentMessage
                                    );
                                } catch (Exception $e) {}
                            }
                        }
                    }
                }
                if (isset($GLOBALS['from_user_chat_photo'])) {
                    set_chat_photo($update, $MadelineProto, false);
                }
                if (isset($GLOBALS['wait_for_whoban'])) {
                    whoban($update, $MadelineProto);
                }
                if (isset($GLOBALS['wait_for_whobanall'])) {
                    whobanall($update, $MadelineProto);
                }
                if (strlen($update['update']['message']['message']) !== 0) {
                    $first_char = substr(
                        $update['update']['message']['message'][0],
                        0, 1
                    );
                    if (preg_match_all('/#/', $first_char, $matches)) {
                            $msg_str = substr(
                                $update['update']['message']['message'], 1
                            );
                            $msg_arr = explode(' ', trim($msg_str));
                            getme($update, $MadelineProto, $msg_arr[0]);
                    }
                    if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                            $msg_str = substr(
                                $update['update']['message']['message'], 1
                            );
                            $msg_id = $update['update']['message']['id'];
                            $msg_arr = explode(' ', trim($msg_str));
                            switch ($msg_arr[0]) {
                        case 'time':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            getloc($update, $MadelineProto, $msg_str);
                            break;

                        case 'weather':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            getweather($update, $MadelineProto, $msg_str);
                            break;

                        case 'add':
                            add_group($update, $MadelineProto);
                            break;

                        case 'rm':
                            rm_group($update, $MadelineProto);
                            break;

                        case 'adminlist':
                            adminlist($update, $MadelineProto);
                            break;

                        case 'kick':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            kickhim($update, $MadelineProto, $msg_str);
                            break;

                        case 'kickme':
                            kickme($update, $MadelineProto);
                            break;

                        case 'del':
                            delmessage($update, $MadelineProto);
                            break;

                        case 'ban':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            banme($update, $MadelineProto, $msg_str);
                            break;

                        case 'banall':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            banall($update, $MadelineProto, $msg_str);
                            break;

                        case 'mute':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            if ($msg_str == "all") {
                                muteall($update, $MadelineProto);
                            } else {
                                muteme($update, $MadelineProto, $msg_str);
                            }
                            break;

                        case 'unmute':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            if ($msg_str == "all") {
                                unmuteall($update, $MadelineProto);
                            } else {
                                unmuteme($update, $MadelineProto, $msg_str);
                            }
                            break;

                        case 'mutelist':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            getmutelist($update, $MadelineProto);
                            break;

                        case 'settings':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            get_settings($update, $MadelineProto);
                            break;

                        case 'setflood':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            setflood($update, $MadelineProto, $msg_str);
                            break;

                        case 'modlist':
                            modlist($update, $MadelineProto);
                            break;

                        case 'banlist':
                            getbanlist($update, $MadelineProto);
                            break;

                        case 'gbanlist':
                            getgbanlist($update, $MadelineProto);
                            break;

                        case 'who':
                            wholist($update, $MadelineProto);
                            break;

                        case 'whofile':
                            whofile($update, $MadelineProto);
                            break;

                        case 'whoban':
                            whoban($update, $MadelineProto);
                            break;

                        case 'whobanall':
                            whobanall($update, $MadelineProto);
                            break;

                        case 'unban':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            unbanme($update, $MadelineProto, $msg_str);
                            break;

                        case 'unbanall':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            unbanall($update, $MadelineProto, $msg_str);
                            break;

                        case 'promote':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            promoteme($update, $MadelineProto, $msg_str);
                            break;

                        case 'demote':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            demoteme($update, $MadelineProto, $msg_str);
                            break;

                        case 'save':
                            if (isset($msg_arr[1])) {
                                    $name = $msg_arr[1];
                                    unset($msg_arr[1]);
                            } else {
                                    $name = "";
                            }
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            saveme($update, $MadelineProto, $msg_str, $name);
                            break;

                        case 'pin':
                            if (isset($msg_arr[1])) {
                                $msg_str = $msg_arr[1];
                                if ($msg_str = "silent") {
                                    $silent = true;
                                } else {
                                    $silent = false;
                                }
                            } else {
                                $silent = false;
                            }
                            pinmessage($update, $MadelineProto, $silent);
                            break;

                        case 'id':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            idme($update, $MadelineProto, $msg_str);
                            break;

                        case 'setphoto':
                            set_chat_photo($update, $MadelineProto);
                            break;

                        case 'public':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            public_toggle($update, $MadelineProto, $msg_str);
                            break;

                        case 'invite':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            invite_user($update, $MadelineProto, $msg_str);
                            break;

                        case 'addadmin':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            addadmin($update, $MadelineProto, $msg_str);
                            break;

                        case 'rmadmin':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            rmadmin($update, $MadelineProto, $msg_str);
                            break;

                        case 'setname':
                            unset($msg_arr[0]);
                            $msg_str = implode(" ", $msg_arr);
                            set_chat_title($update, $MadelineProto, $msg_str);
                            break;

                        case 'newlink':
                            export_new_invite($update, $MadelineProto);
                            break;

                        case 'newgroup':
                            unset($msg_arr[0]);
                            if (isset($msg_arr[1]) && isset($msg_arr[2])) {
                                    $title = $msg_arr[1];
                                    unset($msg_arr[1]);
                                    $about = implode(" ", $msg_arr);
                            } else {
                                    $title = "";
                                    $about = "";
                            }
                            create_new_supergroup(
                                $update,
                                $MadelineProto,
                                $title,
                                $about
                            );
                            break;

                        case 'lock':
                            if (isset($msg_arr[1])) {
                                    $name = $msg_arr[1];
                                    unset($msg_arr[1]);
                            } else {
                                    $name = "";
                            }
                            unset($msg_arr[0]);
                            lockme($update, $MadelineProto, $name);
                            break;

                        case 'unlock':
                            if (isset($msg_arr[1])) {
                                    $name = $msg_arr[1];
                                    unset($msg_arr[1]);
                            } else {
                                    $name = "";
                            }
                            unset($msg_arr[0]);
                            unlockme($update, $MadelineProto, $name);
                            break;

                        case 'end':
                            if (from_master($update, $MadelineProto)) {
                                    exit(0);
                            }
                            break;
                            }
                    }
                }
            }
        }
    }
}


class NewChannelMessageAction extends Thread {
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto) {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run() {
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        switch ($update['update']['message']['action']['_']) {
        case 'messageActionChatAddUser':
            var_dump(true, true, true, true, true);
            NewChatAddUser($update, $MadelineProto);
        break;
        case 'messageActionChatJoinedByLink':
            NewChatJoinedByLink($update, $MadelineProto);
        break;
        case 'messageActionChatDeleteUser':
            NewChatDeleteUser($update, $MadelineProto);
        break;
        }
    }
}

function NewChatAddUser($update, $MadelineProto)
{
    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
    $user_id = $update['update']['message']['action']['users'][0];
    if (is_supergroup($update, $MadelineProto)) {
        $id = catch_id(
            $update,
            $MadelineProto,
            $user_id
        );
        if ($id[0]) {
            $username = $id[2];
        }
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $mention = $id[1];
        if (is_moderated($ch_id)) {
            check_json_array('gbanlist.json', false, false);
            $file = file_get_contents("gbanlist.json");
            $gbanlist = json_decode($file, true);
            if (in_array($mention, $gbanlist)) {
                $message = "I really don't like them!";
                $default['message'] = $message;
                $kick = $MadelineProto->
                channels->kickFromChannel(
                    ['channel' => $peer,
                    'user_id' => $mention,
                    'kicked' => true]
                );
                $sentMessage = $MadelineProto->
                messages->sendMessage(
                    $default
                );
                if (isset($kick)) {
                    \danog\MadelineProto\Logger::log($kick);
                }
                \danog\MadelineProto\Logger::log(
                    $sentMessage
                );
            }
            check_json_array('banlist.json', $ch_id);
            $file = file_get_contents("banlist.json");
            $banlist = json_decode($file, true);
            if (array_key_exists($ch_id, $banlist)) {
                if (in_array($mention, $banlist[$ch_id])) {
                    $message = "NO! They are NOT allowed here!";
                    $default['message'] = $message;
                    $kick = $MadelineProto->
                    channels->kickFromChannel(
                        ['channel' => $peer,
                        'user_id' => $mention,
                        'kicked' => true]
                    );
                    $sentMessage = $MadelineProto->
                    messages->sendMessage(
                        $default
                    );
                    if (isset($kick)) {
                        \danog\MadelineProto\Logger::log($kick);
                    }
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
                }
            }
            $bot_id = $MadelineProto->
                API->datacenter->authorization['user']['id'];
            if ($mention !== $bot_id && empty($default['message'])) {
                    $message = "Hi $username, welcome to $title";
                    $default['entities'] =
                    [['_' => 'inputMessageEntityMentionName',
                    'offset' => 3,
                    'length' => strlen($username),
                    'user_id' => $mention]];
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->
                    messages->sendMessage($default);
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
            } else {
                    $adminid = cache_get_info(
                        $update,
                        $MadelineProto,
                        getenv('MASTER_USERNAME')
                    )['bot_api_id'];
                    $admins = cache_get_chat_info(
                        $update,
                        $MadelineProto
                    );
                if ($admins) {
                    foreach (
                        $admins['participants'] as $key) {
                        if (array_key_exists('user', $key)) {
                            $id = $key['user']['id'];
                        }
                        if ($adminid !== $id) {
                            $master_present = 'false';
                        } else {
                            $master_present = 'true';
                            break;
                        }
                    }
                    if ($master_present == 'false') {
                        $leave = $MadelineProto->
                        channels->leaveChannel(
                            ['channel' => $ch_id]
                        );
                        \danog\MadelineProto\Logger::log($leave);
                    }
                }
            }
        }
    }
}

function NewChatJoinedByLink($update, $MadelineProto)
{
    $user_id = $update['update']['message']['from_id'];
    if (is_supergroup($update, $MadelineProto)) {
        $id = catch_id(
            $update,
            $MadelineProto,
            $user_id
        );
        if ($id[0]) {
            $username = $id[2];
            $mention = $id[1];
        }
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (is_moderated($ch_id)) {
            check_json_array('banlist.json', $ch_id);
            $file = file_get_contents("banlist.json");
            $banlist = json_decode($file, true);
            check_json_array('gbanlist.json', false, false);
            $file = file_get_contents("gbanlist.json");
            $gbanlist = json_decode($file, true);
            if (in_array($mention, $gbanlist)) {
                $message = "I really don't like them!";
                $default['message'] = $message;
                $kick = $MadelineProto->
                channels->kickFromChannel(
                    ['channel' => $peer,
                    'user_id' => $mention,
                    'kicked' => true]
                );
                $sentMessage = $MadelineProto->
                messages->sendMessage(
                    $default
                );
                if (isset($kick)) {
                    \danog\MadelineProto\Logger::log($kick);
                }
                \danog\MadelineProto\Logger::log(
                    $sentMessage
                );
            }
            if (array_key_exists($ch_id, $banlist)) {
                if (in_array($mention, $banlist[$ch_id])) {
                    $message = "NO! They are NOT allowed here!";
                    $default['message'] = $message;
                    $kick = $MadelineProto->
                    channels->kickFromChannel(
                        ['channel' => $peer,
                        'user_id' => $mention,
                        'kicked' => true]
                    );
                    $sentMessage = $MadelineProto->
                    messages->sendMessage(
                        $default
                    );
                    if (isset($kick)) {
                        \danog\MadelineProto\Logger::log($kick);
                    }
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
                }
            }
            $bot_id = $MadelineProto->
                API->datacenter->authorization['user']['id'];
            if ($mention !== $bot_id && empty($default['message'])) {
                    $message = "Hi $username, welcome to $title";
                    $default['entities'] =
                    [['_' => 'inputMessageEntityMentionName',
                    'offset' => 3,
                    'length' => strlen($username),
                    'user_id' => $mention]];
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->
                    messages->sendMessage($default);
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
            } else {
                    $adminid = cache_get_info(
                        $update,
                        $MadelineProto,
                        getenv('MASTER_USERNAME')
                    )['bot_api_id'];
                    $admins = cache_get_chat_info(
                        $update,
                        $MadelineProto
                    );
                if ($admins) {
                    foreach (
                        $admins['participants'] as $key) {
                        if (array_key_exists('user', $key)) {
                            $id = $key['user']['id'];
                        }
                        if ($adminid !== $id) {
                            $master_present = 'false';
                        } else {
                            $master_present = 'true';
                            break;
                        }
                    }
                }
                if ($master_present == 'false') {
                    $leave = $MadelineProto->
                    channels->leaveChannel(
                        ['channel' => $ch_id]
                    );
                    \danog\MadelineProto\Logger::log($leave);
                }
            }
        }
    }
}

function NewChatDeleteUser($update, $MadelineProto)
{
    $user_id = $update['update']['message']['action']['user_id'];
    if (is_supergroup($update, $MadelineProto)) {
        $user_id = $update['update']['message']['from_id'];
        $id = catch_id(
            $update,
            $MadelineProto,
            $user_id
        );
        if ($id[0]) {
            $username = $id[2];
            $mention = $id[1];
        }
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = $chat['title'];
        $msg_id = $update['update']['message']['id'];
        $ch_id = $chat['id'];
        if (is_moderated($ch_id)) {
            $bot_id = $MadelineProto->
            API->datacenter->authorization['user']['id'];
            if ($mention !== $bot_id && empty($default['message'])) {
                $userid_ = $update
                ['update']['message']['action']['user_id'];
                $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'entities' => [['_' =>
                'inputMessageEntityMentionName',
                'offset' => 8,
                'length' => strlen($username),
                'user_id' => $mention]]
                );
                $id = catch_id($update, $MadelineProto, $userid_);
                if ($id[0]) {
                    $username = $id[2];
                    $mention = $id[1];
                }
                $master = cache_get_info(
                    $update,
                    $MadelineProto,
                    getenv('MASTER_USERNAME')
                );
                if ($mention == $master['bot_api_id']) {
                    $leave = $MadelineProto->
                    channels->leaveChannel(
                        ['channel' => $ch_id]
                    );
                    \danog\MadelineProto\Logger::log($leave);
                } else {
                    $message = "Goodbye $username ".":((((";
                    $default['message'] = $message;
                    $sentMessage = $MadelineProto->
                    messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log(
                        $sentMessage
                    );
                }
            }
        }
    }
}


class CheckMuted extends Thread {
    private $update;
    private $MadelineProto;
    public function __construct($update, $MadelineProto) {
        $this->update = $update;
        $this->MadelineProto = $MadelineProto;
    }
    public function run() {
        $update = $this->update;
        $MadelineProto = $this->MadelineProto;
        $chat = parse_chat_data($update, $MadelineProto);
        $msg_id = $update['update']['message']['id'];
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        if (is_moderated($ch_id)) {
            if (is_supergroup($update, $MadelineProto)) {
                check_json_array('mutelist.json', $ch_id);
                $file = file_get_contents("mutelist.json");
                $mutelist = json_decode($file, true);
                $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
                if (is_bot_admin($update, $MadelineProto)) {
                    if (!from_admin_mod($update, $MadelineProto)) {
                        if (array_key_exists($ch_id, $mutelist)) {
                            if (in_array($fromid, $mutelist[$ch_id])
                                or in_array("all", $mutelist[$ch_id])) {
                                try {
                                    $delete = $MadelineProto->
                                    channels->deleteMessages(
                                        ['channel' => $peer,
                                        'id' => [$msg_id]]
                                    );
                                    \danog\MadelineProto\Logger::log($delete);
                                    $thred = new Exec($delete);
                                    $thred->start();
                                    $thred->join();
                                } catch (Exception $e) {}
                            }
                        }
                    }
                }
            }
        }
    }
}
