<?php
/**
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
function saveme($update, $MadelineProto, $msg, $name, $user = false)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'html',
            ];
        $mods = $MadelineProto->responses['saveme']['mods'];
        if ($peerUSER
            or from_admin_mod($update, $MadelineProto, $mods, true)
        ) {
            if ($name) {
                if ($name == 'from') {
                    if ($msg) {
                        $msg = $msg;
                        savefrom($update, $MadelineProto, $msg);

                        return;
                    } else {
                        savefrom($update, $MadelineProto, false);

                        return;
                    }
                }
            }
            if ($name && $msg) {
                $name = htmlentities(cb($name));
                $msg = base64_encode($msg);
                $codename = "<code>$name</code>";
                check_json_array('saved.json', $ch_id);
                $file = file_get_contents('saved.json');
                $saved = json_decode($file, true);
                if (isset($saved[$ch_id])) {
                    if (!array_key_exists('from', $saved[$ch_id])) {
                        $saved[$ch_id]['from'] = [];
                    }
                    if (array_key_exists($name, $saved[$ch_id]['from'])) {
                        unset($saved[$ch_id]['from'][$name]);
                    }
                    $saved[$ch_id][$name] = $msg;
                    file_put_contents('saved.json', json_encode($saved));
                    $str = $MadelineProto->responses['saveme']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $saved[$ch_id] = [];
                    $saved[$ch_id]['from'] = [];
                    $saved[$ch_id][$name] = $msg;
                    file_put_contents('saved.json', json_encode($saved));
                    $str = $MadelineProto->responses['saveme']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['saveme']['help'];
                $default['message'] = $message;
            }
            if (isset($default['message']) && !$user) {
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

function getme($update, $MadelineProto, $name)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        if (bot_present($update, $MadelineProto, true)) {
            $chat = parse_chat_data($update, $MadelineProto);
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            $cont = true;
            $peerUSER = false;
        } else {
            $cont = false;
        }
    }
    if (!$update['update']['message']['out'] && $cont) {
        $name = cb($name);
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode'      => 'markdown',
            ];
        if (isset($update['update']['message']['reply_to_msg_id'])) {
            $default['reply_to_msg_id'] = $update['update']['message']['reply_to_msg_id'];
        }
        check_json_array('saved.json', $ch_id);
        $file = file_get_contents('saved.json');
        $saved = json_decode($file, true);
        $boldname = create_style('bold', 0, $name);
        if (isset($saved[$ch_id])) {
            if ($name !== 'from') {
                foreach ($saved[$ch_id] as $i => $ii) {
                    if (!is_array($i)) {
                        if ($i == $name) {
                            if (base64_encode(base64_decode($saved[$ch_id][$i])) === $saved[$ch_id][$i]) {
                                $message = "$name:\r\n".base64_decode($saved[$ch_id][$i]);
                            } else {
                                $message = "$name:\r\n".$saved[$ch_id][$i];
                            }
                            $default['message'] = $message;
                            $default['entities'] = $boldname;
                        }
                    }
                }
            }
            if (!isset($message)) {
                if (array_key_exists('from', $saved[$ch_id])) {
                    foreach ($saved[$ch_id]['from'] as $i => $ii) {
                        if ($i == $name) {
                            $replyid = $ii['msgid'];
                            $replychat = $ii['chat'];
                            break;
                        }
                    }
                }
            }
            if (isset($message)) {
                try {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                } catch (Exception $e) {
                    var_dump($e->getMessage());
                    $default['message'] = 'HTML of this message formatted incorrectly.';
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                }
            }
            if (isset($replyid)) {
                try {
                    $sentMessage = $MadelineProto->messages->forwardMessages(
                        ['from_peer' => $replychat, 'id' => [$replyid], 'to_peer' => $peer]
                    );
                } catch (Exception $e) {
                }
            }
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function savefrom($update, $MadelineProto, $name)
{
    $uMadelineProto = $MadelineProto->API->uMadelineProto;
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    $replyto = $update['update']['message']['id'];
    $mods = $MadelineProto->responses['savefrom']['mods'];
    $default = [
        'peer'            => $peer,
        'reply_to_msg_id' => $replyto,
        'parse_mode'      => 'html',
    ];
    if ($peerUSER or from_admin_mod($update, $MadelineProto, $mods, true)) {
        if ($name !== 'from') {
            if (array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                $name = cb($name);
                $msg_id = $update['update']['message']['reply_to_msg_id'];
                check_json_array('saved.json', $ch_id);
                $file = file_get_contents('saved.json');
                $saved = json_decode($file, true);
                if (isset($saved[$ch_id])) {
                    if (!array_key_exists('from', $saved[$ch_id])) {
                        $saved[$ch_id]['from'] = [];
                    }
                    $bot_api_id = $MadelineProto->API->bot_api_id;
                    $bot_id = $MadelineProto->API->bot_id;
                    try {
                        $forwardMessage = $MadelineProto->messages->forwardMessages(
                            ['from_peer' => $ch_id, 'id' => [$msg_id], 'to_peer' => $bot_id]
                        );
                    } catch (Exception $e) {
                        $forwardMessage = $uMadelineProto->messages->forwardMessages(
                            ['from_peer' => $ch_id, 'id' => [$msg_id], 'to_peer' => $bot_api_id]
                        );
                    }
                    foreach ($forwardMessage['updates'] as $i) {
                        if ($i['_'] == 'updateMessageID') {
                            $fwd_id = $i['id'];
                            $fwd_chat = $bot_id;
                        }
                    }
                    $saved[$ch_id]['from'][$name] = [];
                    $saved[$ch_id]['from'][$name]['chat'] = $fwd_chat;
                    $saved[$ch_id]['from'][$name]['msgid'] = $fwd_id;
                    if (array_key_exists($name, $saved[$ch_id])) {
                        unset($saved[$ch_id][$name]);
                    }
                    file_put_contents('saved.json', json_encode($saved));
                    $str = $MadelineProto->responses['savefrom']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                } else {
                    $saved[$ch_id] = [];
                    $saved[$ch_id]['from'] = [];
                    $saved[$ch_id]['from'][$name] = [];
                    $saved[$ch_id]['from'][$name]['chat'] = $ch_id;
                    $saved[$ch_id]['from'][$name]['msgid'] = $msg_id;
                    file_put_contents('saved.json', json_encode($saved));
                    $str = $MadelineProto->responses['savefrom']['success'];
                    $repl = [
                        'name' => $name,
                    ];
                    $message = $MadelineProto->engine->render($str, $repl);
                    $default['message'] = $message;
                }
            } else {
                $message = $MadelineProto->responses['savefrom']['help'];
                $default['message'] = $message;
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

function saved_get($update, $MadelineProto)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $title = 'this chat';
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        $msg_id = $update['update']['message']['id'];
        $default = [
            'peer'            => $peer,
            'reply_to_msg_id' => $msg_id,
            ];
        check_json_array('saved.json', $ch_id);
        $file = file_get_contents('saved.json');
        $saved = json_decode($file, true);
        if (isset($saved[$ch_id])) {
            foreach ($saved[$ch_id] as $i => $ii) {
                if ($i !== 'from') {
                    if (!isset($message)) {
                        $name = cb($i);
                        $message = "Saved messages for $title: \n";
                        $offset = mb_strlen($message);
                        $entity = create_style('bold', 0, $message);
                        $message .= '[x] '.$name."\n";
                        $length = $offset + strlen($name);
                    } else {
                        $name = cb($i);
                        $message .= '[x] '.$name."\n";
                        $length = $length + strlen($name);
                    }
                }
            }
            if (array_key_exists('from', $saved[$ch_id])) {
                foreach ($saved[$ch_id]['from'] as $i => $ii) {
                    if (!isset($message)) {
                        $name = cb($i);
                        $message = "Saved messages for $title: \n";
                        $offset = mb_strlen($message);
                        $entity = create_style('bold', 0, $message);
                        $message .= '[x] '.$name."\n";
                        $length = $offset + strlen($name);
                    } else {
                        $name = cb($i);
                        $message .= '[x] '.$name."\n";
                        $length = $length + strlen($name);
                    }
                }
            }
            if (isset($message)) {
                $length = mb_strlen($message) - $offset;
                $default['entities'] = $entity;
                $default['message'] = $message;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            } else {
                $default['message'] = "There are no saved messages for $title";
                $offset = mb_strlen($default['message']) - mb_strlen($title);
                $default['entitites'] = create_style('bold', $offset, mb_strlen($title));
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            }
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function save_clear($update, $MadelineProto, $msg, $user = false)
{
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $ch_id = $peer;
        $cont = true;
        $peerUSER = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $ch_id = $chat['id'];
        $cont = true;
        $peerUSER = false;
    }
    if ($cont) {
        if ($msg !== 'from') {
            $msg_id = $update['update']['message']['id'];
            $default = [
                'peer'            => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode'      => 'html',
                ];
            $mods = 'Why the hell would we let a normal user clear saved messages?';
            if ($peerUSER
                or from_admin_mod($update, $MadelineProto, $mods, true)
            ) {
                if ($msg) {
                    $msg = htmlentities(cb($msg));
                    check_json_array('saved.json', $ch_id);
                    $file = file_get_contents('saved.json');
                    $saved = json_decode($file, true);
                    if (isset($saved[$ch_id])) {
                        if (!array_key_exists('from', $saved[$ch_id])) {
                            $saved[$ch_id]['from'] = [];
                        }
                        if (array_key_exists($msg, $saved[$ch_id]['from'])) {
                            unset($saved[$ch_id]['from'][$msg]);
                            $message = "<code>$msg</code> was successfully cleared";
                            $default['message'] = $message;
                            file_put_contents('saved.json', json_encode($saved));
                        } elseif (array_key_exists($msg, $saved[$ch_id])) {
                            unset($saved[$ch_id][$msg]);
                            $message = "<code>$msg</code> was successfully cleared";
                            $default['message'] = $message;
                            file_put_contents('saved.json', json_encode($saved));
                        } else {
                            $message = "<code>$msg</code> is not a saved message";
                            $default['message'] = $message;
                        }
                    } else {
                        $message = "<code>$msg</code> is not a saved message";
                        $default['message'] = $message;
                    }
                } else {
                    $message = 'Use <code>/save clear message</code> to clear the contents of a message';
                    $default['message'] = $message;
                }
                if (isset($default['message']) && !$user) {
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
}
