<?php
/**
    along with BruhhBot. If not, see <http://www.gnu.org/licenses/>.
 */
function promoteme($update, $MadelineProto, $msg = '')
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['promoteme']['mods'];
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
                if (from_admin($update, $MadelineProto, $mods, true)) {
                    if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                        $id = catch_id($update, $MadelineProto, $msg);
                        if ($id[0]) {
                            $userid = $id[1];
                            $username = $id[2];
                            $mention = html_mention($username, $userid);
                            check_json_array('promoted.json', $ch_id);
                            $file = file_get_contents('promoted.json');
                            $promoted = json_decode($file, true);
                            if (isset($promoted[$ch_id])) {
                                if (!in_array($userid, $promoted[$ch_id])) {
                                    array_push($promoted[$ch_id], $userid);
                                    file_put_contents('promoted.json', json_encode($promoted));
                                    $str = $MadelineProto->responses['promoteme']['success'];
                                    $repl = [
                                        'mention' => $mention,
                                        'title'   => $title,
                                    ];
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                    $alert = "<code>$from_name promoted $username to a moderator in $title</code>";
                                } else {
                                    $str = $MadelineProto->responses['promoteme']['already'];
                                    $repl = [
                                        'mention' => $mention,
                                        'title'   => $title,
                                    ];
                                    $message = $MadelineProto->engine->render($str, $repl);
                                    $default['message'] = $message;
                                }
                            } else {
                                $promoted[$ch_id] = [];
                                array_push($promoted[$ch_id], $userid);
                                file_put_contents('promoted.json', json_encode($promoted));
                                $str = $MadelineProto->responses['promoteme']['success'];
                                $repl = [
                                    'mention' => $mention,
                                    'title'   => $title,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                                $alert = "<code>$from_name promoted $username to a moderator in $title</code>";
                            }
                        } else {
                            $str = $MadelineProto->responses['promoteme']['idk'];
                            $repl = [
                                'msg' => $msg,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
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
}

function demoteme($update, $MadelineProto, $msg = '')
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = "Wow. Mr. I'm not admin over here is trying to DEMOTE people.";
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
            if (from_admin($update, $MadelineProto, $mods, true)) {
                if (!empty($msg) or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
                    $id = catch_id($update, $MadelineProto, $msg);
                    if ($id[0]) {
                        $userid = $id[1];
                        $username = $id[2];
                        $mention = html_mention($username, $userid);
                        check_json_array('promoted.json', $ch_id);
                        $file = file_get_contents('promoted.json');
                        $promoted = json_decode($file, true);
                        if (isset($promoted[$ch_id])) {
                            if (in_array($userid, $promoted[$ch_id])) {
                                if (($key = array_search(
                                    $userid,
                                    $promoted[$ch_id]
                                )) !== false
                                ) {
                                    unset($promoted[$ch_id][$key]);
                                }
                                file_put_contents('promoted.json', json_encode($promoted));
                                $str = $MadelineProto->responses['demoteme']['success'];
                                $repl = [
                                    'mention' => $mention,
                                    'title'   => $title,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                                $alert = "<code>$from_name demoted $username in $title</code>";
                            } else {
                                $str = $MadelineProto->responses['demoteme']['fail'];
                                $repl = [
                                    'mention' => $mention,
                                    'title'   => $title,
                                ];
                                $message = $MadelineProto->engine->render($str, $repl);
                                $default['message'] = $message;
                            }
                        } else {
                            $str = $MadelineProto->responses['demoteme']['success'];
                            $repl = [
                                'mention' => $mention,
                                'title'   => $title,
                            ];
                            $message = $MadelineProto->engine->render($str, $repl);
                            $default['message'] = $message;
                            $alert = "<code>$from_name demoted $username in $title</code>";
                        }
                    } else {
                        $str = $MadelineProto->responses['demoteme']['idk'];
                        $repl = [
                            'msg' => $msg,
                        ];
                        $message = $MadelineProto->engine->render($str, $repl);
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
            if (isset($alert)) {
                alert_moderators($MadelineProto, $ch_id, $alert);
            }
        }
    }
}
