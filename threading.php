<?php

class NewMessage extends Threaded
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
        if (array_key_exists('message', $update['update']['message'])) {
            if ($update['update']['message']['message'] !== '') {
                $first_char = substr(
                    $update['update']['message']
                    ['message'][0], 0, 1
                );
                if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                    $msg = substr(
                        $update['update']['message']
                        ['message'], 1
                    );
                    $msg_id = $update['update']['message']['id'];
                    $fromid = $update['update']['message']['from_id'];
                    if ($fromid == $MadelineProto->API->bot_id or $fromid == $MadelineProto->API->bot_api_id) return;
                    $default = array(
                        'peer' => $fromid,
                        'reply_to_msg_id' => $msg_id,
                        'parse_mode' => 'html'
                    );
                    check_json_array('gbanlist.json', false, false);
                    $file = file_get_contents("gbanlist.json");
                    $gbanlist = json_decode($file, true);
                    if (array_key_exists($fromid, $gbanlist)) {
                        try {
                            $message = "You know not to message me. You have been reported as spam. #savage";
                            $default['message'] = $message;
                            $report = $uMadelineProto->messages->reportSpam(['peer' => $fromid]);
                            $block = $uMadelineProto->contacts->block(['id' => $fromid]);
                            $sentMessage = $MadelineProto->
                            messages->sendMessage(
                                $default
                            );
                        } catch (Exception $e) {}
                        if (isset($report)) {
                            \danog\MadelineProto\Logger::log($report);
                        }
                        if (isset($block)) {
                            \danog\MadelineProto\Logger::log($block);
                        }
                        if (isset($sentMessage)) {
                            \danog\MadelineProto\Logger::log(
                                $sentMessage
                            );
                        }
                    }
                    $msg_id = $update['update']['message']['id'];
                    $botuser = strtolower(getenv("BOT_API_USERNAME"));
                    $msg = substr(
                        $update['update']['message']['message'], 1
                    );
                    $msg_arr = explode(' ', trim($msg));
                    $msg = preg_replace("/$botuser/", "", strtolower($msg_arr[0]));
                    try {
                        switch (strtolower($msg)) {
                        case 'start':
                            start_message($update, $MadelineProto);
                            break;

                        case 'help':
                            help_message($update, $MadelineProto);
                            break;

                        case 'time':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            gettime($update, $MadelineProto, $msg);
                            break;

                        case 'weather':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            getweather($update, $MadelineProto, $msg);
                            break;

                        case 'id':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            idme($update, $MadelineProto, $msg);
                            break;

                        case 'stats':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            get_user_stats($update, $MadelineProto, $msg);
                            break;

                        case 'leave':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            leave_setting($update, $MadelineProto, $msg);
                            break;

                        case 'save':
                            if (isset($msg_arr[1])) {
                                    $name = $msg_arr[1];
                                    unset($msg_arr[1]);
                            } else {
                                $name = false;
                            }
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            $name_ = strtolower($name);
                            if ($name_ == "clear") {
                                save_clear($update, $MadelineProto, $msg);
                            } else {
                                saveme($update, $MadelineProto, $msg, $name);
                            }
                            break;

                        case 'saved':
                            saved_get($update, $MadelineProto);
                            break;

                        case 'newgroup':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            create_new_supergroup(
                                $update,
                                $MadelineProto,
                                $msg
                            );
                            break;

                        case 'broadcast':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            broadcast_to_all($update, $MadelineProto, $msg);
                            break;

                        case 'join':
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            if ($msg=="") $msg = false;
                            import_chat_invite($update, $MadelineProto, $msg);
                            break;

                        case 'end':
                            if (from_master($update, $MadelineProto)) {
                                \danog\MadelineProto\Serialization::serialize(
                                    'session.madeline',
                                    $MadelineProto
                                ).PHP_EOL;
                                exit(0);
                            }
                            break;
                        }
                    } catch (Exception $e) {}
                }
            }
            if (array_key_exists("fwd_from", $update['update']['message'])) {
                if (array_key_exists("from_id", $update['update']['message']['fwd_from'])) {
                    $fwd_id = $update['update']['message']['fwd_from']['from_id'];
                    get_user_stats($update, $MadelineProto, $fwd_id);
                }
            }
        }
    }
}

class NewChannelMessage extends Threaded
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
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        if (array_key_exists('message', $update['update']['message'])
            && is_string($update['update']['message']['message'])
        ) {
            if (is_supergroup($update, $MadelineProto)) {

        $chat = parse_chat_data($update, $MadelineProto);
        $msg_id = $update['update']['message']['id'];
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $this->muted = false;
        if (is_moderated($ch_id) && is_supergroup($update, $MadelineProto) && bot_present($update, $MadelineProto, true)) {
            check_json_array('mutelist.json', $ch_id);
            $file = file_get_contents("mutelist.json");
            $mutelist = json_decode($file, true);
            $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
            if (isset($update['update']['message']['via_bot_id'])) {
                $from_users = [$fromid, $update['update']['message']['via_bot_id']];
            } else {
                $from_users = [$fromid];
            }
            if (is_bot_admin($update, $MadelineProto)) {
                if (!from_admin_mod($update, $MadelineProto)) {
                    if (isset($mutelist[$ch_id])) {
                        foreach ($from_users as $userid) {
                            if (in_array($userid, $mutelist[$ch_id])
                                or in_array("all", $mutelist[$ch_id])
                            ) {
                                try {
                                    $delete = $uMadelineProto->
                                    channels->deleteMessages(
                                        ['channel' => $peer,
                                        'id' => [$msg_id]]
                                    );
                                    \danog\MadelineProto\Logger::log($delete);
                                } catch (Exception $e) {}
                                $this->muted = true;
                                break;
                            } else {
                                $this->muted = false;
                            }
                        }
                    }
                }
            }
        }
                if ($this->muted) {
                    return;
                }
                $chat = parse_chat_data($update, $MadelineProto);
                $peer = $chat['peer'];
                $ch_id = $chat['id'];
                check_json_array('banlist.json', $ch_id);
                $file = file_get_contents("banlist.json");
                $banlist = json_decode($file, true);
                $msg_id = $update['update']['message']['id'];
                if (is_bot_admin($update, $MadelineProto) && !from_admin_mod($update, $MadelineProto)) {
                    $default = array(
                        'peer' => $peer,
                        'reply_to_msg_id' => $msg_id,
                    );
                    check_json_array('gbanlist.json', false, false);
                    $file = file_get_contents("gbanlist.json");
                    $gbanlist = json_decode($file, true);
                    if (array_key_exists($fromid, $gbanlist) && !is_admin($update, $MadelineProto, $fromid)) {
                        try {
                            $message = "I really don't like them!";
                            $default['message'] = $message;
                            $delete = $uMadelineProto->
                            channels->deleteMessages(
                                ['channel' => $peer,
                                'id' => [$msg_id]]
                            );
                            $kick = $uMadelineProto->
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
                        } catch (Exception $e) {
                            }
                    }
                    if (isset($banlist[$ch_id])) {
                        if (in_array($fromid, $banlist[$ch_id]) && !is_admin($update, $MadelineProto, $fromid)) {
                            try {
                                $message = "NO! They are NOT allowed here!";
                                $default['message'] = $message;
                                $delete = $uMadelineProto->
                                channels->deleteMessages(
                                    ['channel' => $peer,
                                    'id' => [$msg_id]]
                                );
                                $kick = $uMadelineProto->
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
                            } catch (Exception $e) {
                            }
                        }
                    }
                }
                if (isset($MadelineProto->from_user_chat_photo)) {
                    set_chat_photo($update, $MadelineProto, false);
                }
                if (isset($MadelineProto->wait_for_whoban)) {
                    whoban($update, $MadelineProto);
                }
                if (isset($MadelineProto->wait_for_whobanall)) {
                    whobanall($update, $MadelineProto);
                }
                if (strlen($update['update']['message']['message']) !== 0) {
                    $first_char = substr(
                        $update['update']['message']['message'][0],
                        0, 1
                    );
                    if (preg_match_all('/#/', $first_char, $matches)) {
                            $msg = substr(
                                $update['update']['message']['message'], 1
                            );
                            $msg_arr = explode(' ', trim($msg));
                            getme($update, $MadelineProto, $msg_arr[0]);
                    }
                    if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                        $botuser = strtolower(getenv("BOT_API_USERNAME"));
                        $msg = substr(
                            $update['update']['message']['message'], 1
                        );
                        $msg_arr = explode(' ', trim($msg));
                        $msg = preg_replace("/$botuser/", "", strtolower($msg_arr[0]));
                        $msg_id = $update['update']['message']['id'];
                        try {
                            switch ($msg) {
                            case 'time':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                gettime($update, $MadelineProto, $msg);
                                break;

                            case 'weather':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                getweather($update, $MadelineProto, $msg);
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
                                $msg = implode(" ", $msg_arr);
                                kickhim($update, $MadelineProto, $msg);
                                break;

                            case 'kickme':
                                kickme($update, $MadelineProto);
                                break;

                            case 'del':
                                if (isset($msg_arr[1])) {
                                    unset($msg_arr[0]);
                                    $msg = implode(" ", $msg_arr);
                                    delmessage_user($update, $MadelineProto, $msg);
                                } else {
                                    delmessage($update, $MadelineProto);
                                }
                                break;

                            case 'purge':
                                purgemessage($update, $MadelineProto);
                                break;

                            case 'ban':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                banme($update, $MadelineProto, $msg);
                                break;

                            case 'banall':
                                unset($msg_arr[0]);
                                if (!empty($msg_arr)) {
                                    $last = key(array_slice($msg_arr, -1, 1, TRUE));
                                    if ($msg_arr[$last] == "silent") {
                                        $silent = false;
                                        unset($msg_arr[$last]);
                                    } else {
                                        $silent = true;
                                    }
                                } else {
                                    $silent = true;
                                }
                                if (!empty($msg_arr)) {
                                    if (isset($msg_arr[1])) {
                                        $msg = $msg_arr[1];
                                        unset($msg_arr[1]);
                                    } else {
                                        $msg = "";
                                    }
                                }
                                if ($msg == "banall") {
                                    $msg = "";
                                }
                                $reason = implode(" ", $msg_arr);
                                banall($update, $MadelineProto, $msg, $reason,$silent);
                                break;

                            case 'mute':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                $msg_ = strtolower($msg);
                                if ($msg_ == "all") {
                                    muteall($update, $MadelineProto);
                                } else {
                                    muteme($update, $MadelineProto, $msg);
                                }
                                break;

                            case 'unmute':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                $msg_ = strtolower($msg);
                                if ($msg_ == "all") {
                                    unmuteall($update, $MadelineProto);
                                } else {
                                    unmuteme($update, $MadelineProto, $msg);
                                }
                                break;

                            case 'mutelist':
                                getmutelist($update, $MadelineProto);
                                break;

                            case 'settings':
                                settings_menu($update, $MadelineProto);
                                break;

                            case 'setflood':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                setflood($update, $MadelineProto, $msg);
                                break;

                            case 'welcome':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                set_chat_welcome($update, $MadelineProto, $msg);
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
                                $msg = implode(" ", $msg_arr);
                                unbanme($update, $MadelineProto, $msg);
                                break;

                            case 'unbanall':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                unbanall($update, $MadelineProto, $msg);
                                break;

                            case 'stats':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                get_user_stats($update, $MadelineProto, $msg);
                                break;

                            case 'promote':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                promoteme($update, $MadelineProto, $msg);
                                break;

                            case 'demote':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                demoteme($update, $MadelineProto, $msg);
                                break;

                            case 'save':
                                if (isset($msg_arr[1])) {
                                        $name = $msg_arr[1];
                                        unset($msg_arr[1]);
                                } else {
                                    $name = false;
                                }
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                $name_ = strtolower($name);
                                if ($name_ == "clear") {
                                    save_clear($update, $MadelineProto, $msg);
                                } else {
                                    saveme($update, $MadelineProto, $msg, $name);
                                }
                                break;

                            case 'saved':
                                saved_get($update, $MadelineProto);
                                break;

                            case 'pin':
                                if (isset($msg_arr[1])) {
                                    $msg = $msg_arr[1];
                                    $msg_ = strtolower($msg);
                                    if ($msg_ = "silent") {
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
                                $msg = implode(" ", $msg_arr);
                                idme($update, $MadelineProto, $msg);
                                break;

                            case 'setphoto':
                                set_chat_photo($update, $MadelineProto);
                                break;

                            case 'public':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                public_toggle($update, $MadelineProto, $msg);
                                break;

                            case 'invite':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                invite_user($update, $MadelineProto, $msg);
                                break;

                            case 'addadmin':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                addadmin($update, $MadelineProto, $msg);
                                break;

                            case 'rmadmin':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                rmadmin($update, $MadelineProto, $msg);
                                break;

                            case 'setname':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                set_chat_title($update, $MadelineProto, $msg);
                                break;

                            case 'setabout':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                set_chat_about($update, $MadelineProto, $msg);
                                break;

                            case 'setrules':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                set_chat_rules($update, $MadelineProto, $msg);
                                break;

                            case 'rules':
                                unset($msg_arr[0]);
                                $msg = implode(" ", $msg_arr);
                                get_chat_rules($update, $MadelineProto);
                                break;

                            case 'newlink':
                                export_new_invite($update, $MadelineProto);
                                break;

                            case 'lock':
                                if (isset($msg_arr[1])) {
                                        $name = strtolower($msg_arr[1]);
                                        unset($msg_arr[1]);
                                } else {
                                    $name = "";
                                }
                                unset($msg_arr[0]);
                                lockme($update, $MadelineProto, $name);
                                break;

                            case 'unlock':
                                if (isset($msg_arr[1])) {
                                        $name = strtolower($msg_arr[1]);
                                        unset($msg_arr[1]);
                                } else {
                                    $name = "";
                                }
                                unset($msg_arr[0]);
                                unlockme($update, $MadelineProto, $name);
                                break;

                            case 'groupuser':
                                if (isset($msg_arr[1])) {
                                        $name = strtolower($msg_arr[1]);
                                        unset($msg_arr[1]);
                                } else {
                                    $name = "";
                                }
                                unset($msg_arr[0]);
                                set_chat_username($update, $MadelineProto, $name);
                                break;
                            }
                        } catch (Exception $e) {}
                    }
                }
            }
        }
    }
}


class NewChannelMessageAction extends Threaded
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
        switch ($update['update']['message']['action']['_']) {
        case 'messageActionPinMessage':
            if (!$update['update']['message']['out']) {
                pinalert($update, $MadelineProto);
            }
            break;
        case 'messageActionChatAddUser':
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
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
    $user_id = $update['update']['message']['action']['users'][0];
    $chat = parse_chat_data($update, $MadelineProto);
    $peer = $chat['peer'];
    if (isset($MadelineProto->API->is_bot_present[$peer])) unset($MadelineProto->API->is_bot_present[$peer]);
    if (bot_present($update, $MadelineProto, true)) {
        if (is_supergroup($update, $MadelineProto)) {
            $id = catch_id(
                $update,
                $MadelineProto,
                $user_id
            );
            if ($id[0]) {
                $username = $id[2];
                $firstname = $id[3];
            }
            $msg_id = $update['update']['message']['id'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html'
                );
            $mention = $id[1];
            if (is_moderated($ch_id)) {
                check_json_array('gbanlist.json', false, false);
                $file = file_get_contents("gbanlist.json");
                $gbanlist = json_decode($file, true);
                if (array_key_exists($mention, $gbanlist)) {
                    try {
                        $message = "I really don't like them!";
                        $default['message'] = $message;
                        $kick = $uMadelineProto->
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
                    } catch (Exception $e) {}
                }
                check_json_array('banlist.json', $ch_id);
                $file = file_get_contents("banlist.json");
                $banlist = json_decode($file, true);
                if (isset($banlist[$ch_id])) {
                    if (in_array($mention, $banlist[$ch_id])) {
                        try {
                            $message = "NO! They are NOT allowed here!";
                            $default['message'] = $message;
                            $kick = $uMadelineProto->
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
                        } catch (Exception $e) {}
                    }
                }
                $bot_id = $MadelineProto->API->bot_id;
                $bot_api_id = $MadelineProto->API->bot_api_id;
                if ($mention !== $bot_api_id && empty($default['message'])) {
                    check_json_array('settings.json', $ch_id);
                    $file = file_get_contents("settings.json");
                    $settings = json_decode($file, true);
                    if (isset($settings[$ch_id])) {
                        if (!isset($settings[$ch_id]["welcome"])) {
                            $settings[$ch_id]["welcome"] = true;
                        }
                        if ($settings[$ch_id]["welcome"]) {
                            $mention2 = html_mention($username, $mention);
                            if (isset($settings[$ch_id]["custom_welcome"])) {
                                $str = $settings[$ch_id]["custom_welcome"];
                                $repl = array(
                                    "name"  => $firstname,
                                    "username" => $username,
                                    "mention" => $mention2,
                                    "id" => $mention,
                                    "title" => $title
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                            } else {
                                $message = "Hi $mention2, welcome to <b>$title</b>";
                            }
                            if (isset($settings[$ch_id]["show_rules_welcome"])) {
                                if ($settings[$ch_id]["show_rules_welcome"]) {
                                    $botusername = preg_replace("/@/", "",getenv("BOT_API_USERNAME"));
                                    $url = "https://telegram.me/$botusername?start=rules-$ch_id";
                                    $keyboardButtonUrl = ['_' => 'keyboardButtonUrl', 'text' => "Rules", 'url' => $url, ];
                                    $buttons = [$keyboardButtonUrl];
                                    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                                    $rows = [$row];
                                    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
                                    $default['reply_markup'] = $replyInlineMarkup;
                                }
                            }
                            $default['message'] = $message;
                            try {
                                $sentMessage = $MadelineProto->
                                messages->sendMessage($default);
                                \danog\MadelineProto\Logger::log(
                                    $sentMessage
                                );
                            } catch (Exception $e) {}
                        }
                    }
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
                                $master_present = false;
                            } else {
                                $master_present = true;
                                break;
                            }
                        }
                        if (!$master_present) {
                            check_json_array('leave.json', false, false);
                            $file = file_get_contents("leave.json");
                            $leave_ = json_decode($file, true);
                            if (in_array('on', $leave_)) {
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
    }
}

function NewChatJoinedByLink($update, $MadelineProto)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    $user_id = $update['update']['message']['from_id'];
    $chat = parse_chat_data($update, $MadelineProto);
    $peer = $chat['peer'];
    if (isset($MadelineProto->API->is_bot_present[$peer])) unset($MadelineProto->API->is_bot_present[$peer]);
    if (bot_present($update, $MadelineProto, true)) {
        if (is_supergroup($update, $MadelineProto)) {
            $id = catch_id(
                $update,
                $MadelineProto,
                $user_id
            );
            if ($id[0]) {
                $username = $id[2];
                $mention = $id[1];
                $firstname = $id[3];
            }
            $msg_id = $update['update']['message']['id'];
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html'
                );
            if (is_moderated($ch_id)) {
                check_json_array('banlist.json', $ch_id);
                $file = file_get_contents("banlist.json");
                $banlist = json_decode($file, true);
                check_json_array('gbanlist.json', false, false);
                $file = file_get_contents("gbanlist.json");
                $gbanlist = json_decode($file, true);
                if (array_key_exists($mention, $gbanlist)) {
                    try {
                        $message = "I really don't like them!";
                        $default['message'] = $message;
                        $kick = $uMadelineProto->
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
                    } catch (Exception $e) {}
                }
                if (isset($banlist[$ch_id])) {
                    if (in_array($mention, $banlist[$ch_id])) {
                        try {
                            $message = "NO! They are NOT allowed here!";
                            $default['message'] = $message;
                            $kick = $uMadelineProto->
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
                        } catch (Exception $e) {}
                    }
                }
                $bot_id = $MadelineProto->API->bot_id;
                if ($mention !== $bot_id && empty($default['message'])) {
                    check_json_array('settings.json', $ch_id);
                    $file = file_get_contents("settings.json");
                    $settings = json_decode($file, true);
                    if (isset($settings[$ch_id])) {
                        if (!isset($settings[$ch_id]["welcome"])) {
                            $settings[$ch_id]["welcome"] = true;
                        }
                        if ($settings[$ch_id]["welcome"]) {
                            $mention2 = html_mention($username, $mention);
                            if (isset($settings[$ch_id]["custom_welcome"])) {
                                $str = $settings[$ch_id]["custom_welcome"];
                                $repl = array(
                                    "name" => $firstname,
                                    "username" => $username,
                                    "mention" => $mention2,
                                    "id" => $mention,
                                    "title" => $title
                                );
                                $message = $MadelineProto->engine->render($str, $repl);
                            } else {
                                $message = "Hi $mention2, welcome to <b>$title</b>";
                            }
                            if (isset($settings[$ch_id]["show_rules_welcome"])) {
                                if ($settings[$ch_id]["show_rules_welcome"]) {
                                    $botusername = preg_replace("/@/", "",getenv("BOT_API_USERNAME"));
                                    $url = "https://telegram.me/$botusername?start=rules-$ch_id";
                                    $keyboardButtonUrl = ['_' => 'keyboardButtonUrl', 'text' => "Rules", 'url' => $url, ];
                                    $buttons = [$keyboardButtonUrl];
                                    $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                                    $rows = [$row];
                                    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
                                    $default['reply_markup'] = $replyInlineMarkup;
                                }
                            }
                            $default['message'] = $message;
                            try {
                                $sentMessage = $MadelineProto->
                                messages->sendMessage($default);
                                \danog\MadelineProto\Logger::log(
                                    $sentMessage
                                );
                            } catch (Exception $e) {}
                        }
                    }
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
                                $master_present = false;
                            } else {
                                $master_present = true;
                                break;
                            }
                        }
                    }
                    if (!$master_present) {
                        check_json_array('leave.json', false, false);
                        $file = file_get_contents("leave.json");
                        $leave_ = json_decode($file, true);
                        if (in_array('on', $leave_)) {
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
}

function NewChatDeleteUser($update, $MadelineProto)
{
    $chat = parse_chat_data($update, $MadelineProto);
    $peer = $chat['peer'];
    if (isset($MadelineProto->API->is_bot_present[$peer])) unset($MadelineProto->API->is_bot_present[$peer]);
    if (bot_present($update, $MadelineProto, true)) {
        $user_id = $update['update']['message']['action']['user_id'];
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
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            if (is_moderated($ch_id)) {
                $bot_id = $MadelineProto->API->bot_id;
                if ($mention !== $bot_id && empty($default['message'])) {
                    $userid = $update
                    ['update']['message']['action']['user_id'];
                    $entity = create_mention(8, $username, $mention);
                    $default = array(
                    'peer' => $peer,
                    'reply_to_msg_id' => $msg_id,
                    'entities' => $entity
                    );
                    $id = catch_id($update, $MadelineProto, $userid);
                    if ($id[0]) {
                        $username = $id[2];
                        $mention = $id[1];
                    }
                    if ($mention == $MadelineProto->API->bot_id) {
                        $leave = $MadelineProto->
                        channels->leaveChannel(
                            ['channel' => $ch_id]
                        );
                        \danog\MadelineProto\Logger::log($leave);
                    } else {
                        try {
                            $message = "Goodbye $username :((((";
                            $default['message'] = $message;
                            $sentMessage = $MadelineProto->
                            messages->sendMessage(
                                $default
                            );
                            \danog\MadelineProto\Logger::log(
                                $sentMessage
                            );
                        } catch (Exception $e) {
                        }
                    }
                }
            }
        }
    }
}

class NewChannelMessageUserBot extends Threaded
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
        $fromid = cache_from_user_info($update, $MadelineProto);
        if (!isset($fromid['bot_api_id'])) {
            return;
        }
        $fromid = $fromid['bot_api_id'];
        if (array_key_exists('message', $update['update']['message'])
            && is_string($update['update']['message']['message'])
        ) {
            if (is_supergroup($update, $MadelineProto)) {

        $chat = parse_chat_data($update, $MadelineProto);
        $msg_id = $update['update']['message']['id'];
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $this->muted = false;
        if (is_moderated($ch_id) && is_supergroup($update, $MadelineProto) && bot_present($update, $MadelineProto, true, false, true)) {
            check_json_array('mutelist.json', $ch_id);
            $file = file_get_contents("mutelist.json");
            $mutelist = json_decode($file, true);
            $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
            if (isset($update['update']['message']['via_bot_id'])) {
                $from_users = [$fromid, $update['update']['message']['via_bot_id']];
            } else {
                $from_users = [$fromid];
            }
            try {
                if (in_array($fromid, json_decode(getenv('MUTED_FOREVER'), true))) {
                    $muted_forever = true;
                } else {
                    $muted_forever = false;
                }
            } catch (Exception $e) {
                $muted_forever = false;
            }
            if (is_bot_admin($update, $MadelineProto)) {
                if (!from_admin_mod($update, $MadelineProto) or $muted_forever) {
                    if (isset($mutelist[$ch_id]) or $muted_forever) {
                        foreach ($from_users as $userid) {
                            if (in_array($userid, $mutelist[$ch_id])
                                or in_array("all", $mutelist[$ch_id])
                                or $muted_forever
                            ) {
                                try {
                                    $delete = $MadelineProto->
                                    channels->deleteMessages(
                                        ['channel' => $peer,
                                        'id' => [$msg_id]]
                                    );
                                    \danog\MadelineProto\Logger::log($delete);
                                } catch (Exception $e) {}
                                $this->muted = true;
                                break;
                            } else {
                                $this->muted = false;
                            }
                        }
                    }
                }
            }
        }
                if ($this->muted) {
                    return;
                }
                $chat = parse_chat_data($update, $MadelineProto);
                $peer = $chat['peer'];
                $ch_id = $chat['id'];
                check_json_array('banlist.json', $ch_id);
                $file = file_get_contents("banlist.json");
                $banlist = json_decode($file, true);
                $msg_id = $update['update']['message']['id'];
                if (is_bot_admin($update, $MadelineProto) && !from_admin_mod($update, $MadelineProto)) {
                    $default = array(
                        'peer' => $peer,
                        'reply_to_msg_id' => $msg_id,
                    );
                    check_json_array('gbanlist.json', false, false);
                    $file = file_get_contents("gbanlist.json");
                    $gbanlist = json_decode($file, true);
                    if (array_key_exists($fromid, $gbanlist)) {
                        try {
                            $kick = $MadelineProto->
                            channels->kickFromChannel(
                                ['channel' => $peer,
                                'user_id' => $fromid,
                                'kicked' => true]
                            );
                            if (isset($kick)) {
                                \danog\MadelineProto\Logger::log($kick);
                            }
                        } catch (Exception $e) {}
                    }
                    if (isset($banlist[$ch_id])) {
                        if (in_array($fromid, $banlist[$ch_id])) {
                            try {
                                $kick = $MadelineProto->
                                channels->kickFromChannel(
                                    ['channel' => $peer,
                                    'user_id' => $fromid,
                                    'kicked' => true]
                                );
                                if (isset($kick)) {
                                    \danog\MadelineProto\Logger::log($kick);
                                }
                            } catch (Exception $e) {}
                        }
                    }
                }
                if (strlen($update['update']['message']['message']) !== 0) {
                    $first_char = substr(
                        $update['update']['message']['message'][0],
                        0, 1
                    );
                    if (preg_match_all('/#/', $first_char, $matches)) {
                            $msg = substr(
                                $update['update']['message']['message'], 1
                            );
                            $msg_arr = explode(' ', trim($msg));
                            getme($update, $MadelineProto, $msg_arr[0]);
                    }
                    if (preg_match_all('/[\!\#\/]/', $first_char, $matches)) {
                        $botuser = strtolower(getenv("BOT_API_USERNAME"));
                        $msg = substr(
                            $update['update']['message']['message'], 1
                        );
                        $msg_arr = explode(' ', trim($msg));
                        $msg = preg_replace("/$botuser/", "", strtolower($msg_arr[0]));
                        $msg_id = $update['update']['message']['id'];
                        switch ($msg) {
                        case 'save':
                            if (isset($msg_arr[1])) {
                                    $name = $msg_arr[1];
                                    unset($msg_arr[1]);
                            } else {
                                $name = false;
                            }
                            unset($msg_arr[0]);
                            $msg = implode(" ", $msg_arr);
                            $name_ = strtolower($name);
                            switch ($name_) {
                            case 'clear':
                                save_clear($update, $MadelineProto, $msg);
                                break;
                            case 'from':
                                break;
                            default:
                                saveme($update, $MadelineProto, $msg, $name, true);
                            }
                            break;

                        case 'pin':
                            if (isset($msg_arr[1])) {
                                $msg = $msg_arr[1];
                                $msg_ = strtolower($msg);
                                if ($msg_ = "silent") {
                                    $silent = true;
                                } else {
                                    $silent = false;
                                }
                            } else {
                                $silent = false;
                            }
                            pinmessage($update, $MadelineProto, $silent, true);
                            break;
                        }
                    }
                }
            }
        }
    }
}

class UserBotUpdates extends Threaded
{
    private $updates;
    private $uMadelineProto;
    public function __construct($updates, $uMadelineProto)
    {
        $this->updates = $updates;
        $this->uMadelineProto = $uMadelineProto;
    }
    public function run()
    {
        require_once 'require_exceptions.php';
        $updates = $this->updates;
        $uMadelineProto = $this->uMadelineProto;
        $pool = new Pool(100);
        foreach ($updates as $update) {
            switch ($update['update']['_']) {
            case 'updateNewChannelMessage':
                if (is_supergroup($update, $uMadelineProto)) {
                    if (!array_key_exists("from_id", $update['update']['message'])) break;
                    try {
                        $user = cache_from_user_info($update, $uMadelineProto);
                    } catch (Exception $e) {
                        break;
                    }
                    if (!array_key_exists("type", $user)) break;
                    if ($user['type'] != "bot") {
                        $uMadelineProto->flooder['num'] = 0;
                        $uMadelineProto->flooder['user'] = $update['update']['message']['from_id'];
                        break;
                    }
                    if (isset($user['bot_api_id'])) {
                        if ($user['bot_api_id'] == $uMadelineProto->API->bot_api_id) break;
                    }
                    $pool->submit(new check_locked_user($update, $uMadelineProto));
                    $pool->submit(new check_flood_user($update, $uMadelineProto));
                    $pool->submit(new NewChannelMessageUserBot($update, $uMadelineProto));
                }
            break;
            }
        }
    }
}

class BotAPIUpdates extends Threaded
{
    private $updates;
    private $MadelineProto;
    public function __construct($updates, $MadelineProto)
    {
        $this->updates = $updates;
        $this->MadelineProto = $MadelineProto;
    }
    public function run()
    {
        require_once 'require_exceptions.php';
        $updates = $this->updates;
        $MadelineProto = $this->MadelineProto;
        $uMadelineProto = $MadelineProto->API->uMadelineProto;
        $pool = new Pool(100);
        foreach ($updates as $update) {
            switch ($update['update']['_']) {
            case 'updateNewMessage':
                if (is_peeruser($update, $MadelineProto)) {
                    $pool->submit(new NewMessage($update, $MadelineProto));
                }
            break;
            case 'updateNewChannelMessage':
                if (is_supergroup($update, $MadelineProto)) {
                    $pool->submit(new check_locked($update, $MadelineProto));
                    $pool->submit(new check_flood($update, $MadelineProto));
                    $pool->submit(new NewChannelMessage($update, $MadelineProto));
                    if (array_key_exists('action', $update['update']['message'])) {
                        $pool->submit(new NewChannelMessageAction($update, $MadelineProto));
                    }
                }
            break;
            case 'updateBotCallbackQuery':
                if (is_supergroup($update, $MadelineProto) or is_peeruser($update, $MadelineProto)) {
                    $pool->submit(new BotCallbackQuery($update, $MadelineProto));
                }
            }
        }
    }
}
