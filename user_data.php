<?php

function get_user_stats($update, $MadelineProto, $user) 
{
    $msg_id = $update['update']['message']['id'];
    if (is_peeruser($update, $MadelineProto)) {
        $peer = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['from_id']
        )['bot_api_id'];
        $cont = true;
    }
    if (is_supergroup($update, $MadelineProto)) {
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $cont = true;
    }
    if (!$update['update']['message']['out'] && isset($cont)) {
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html'
        );
        if ($user !== "" or array_key_exists('reply_to_msg_id', $update['update']['message'])) {
            $msg_id = $update['update']['message']['id'];
            $catch = catch_id($update, $MadelineProto, $user);
            if ($catch[0]) {
                $id = $catch[1];
                $user_data = user_specific_data($update, $MadelineProto, $id);
                $id = $user_data['id'];
                $firstname = htmlentities($user_data['firstname']);
                if (array_key_exists('lastname', $user_data)) {
                    $lastname = htmlentities($user_data['lastname']);
                }
                if (array_key_exists('username', $user_data)) {
                    $username = $user_data['username'];
                }
                if (array_key_exists('banned', $user_data)) {
                    $banned = $user_data['banned'];
                }
                if (array_key_exists('gbanned', $user_data)) {
                    $gbanned = $user_data['gbanned'];
                }
                $message = "<b>User info</b>:\r\nFirst Name: $firstname\r\n";
                if (isset($lastname)) {
                    $message = $message."Last Name: $lastname \r\n";
                }
                $message = $message."ID: $id\r\n";
                if (isset($username)) {
                    $message = $message."Username: $username \r\n";
                }
                if (isset($banned)) {
                    foreach ($banned as $key => $value) {
                        if ($value !== []) {
                            $title = htmlentities($value['title']);
                            $chatid = $value['id'];
                            if (!isset($ban)) {
                                $ban = "\r\n<b>Banned from:</b>\r\n$title [$chatid]\r\n";
                            } else {
                                $ban = $ban."$title - $chatid\r\n";
                            }
                        }
                    }
                    $message = $message.$ban;
                } else {
                    $message = $message."\r\nNot banned from any of my chats.\r\n";
                }
                if (isset($gbanned)) {
                    $message = $message."\r\nGlobally banned: <b>Yes</b>";
                } else {
                    $message = $message."\r\nGlobally banned: <b>No</b>";
                }
            } else {
                $message = "I don't know anyone by the name of $user";
            }
        } else {
            $message = "Use <code>/stats @username</code> to get some info about a user";
        }
        if (isset($message)) {
            $default['message'] = $message;
            var_dump($default);
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
