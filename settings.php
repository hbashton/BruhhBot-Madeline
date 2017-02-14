<?php

function get_settings($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        if (!from_admin_mod($update, $MadelineProto)) {
            $peer = cache_get_info(
                $update,
                $MadelineProto,
                $update['update']['message']['from_id']
            )['bot_api_id'];
        } else {
            $peer = $chat['peer'];
        }
        $title = $chat['title'];
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            'parse_mode' => 'html',
            );
        if (is_moderated($ch_id)) {
            $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
            check_json_array('locked.json', $ch_id);
            $file = file_get_contents("locked.json");
            $locked = json_decode($file, true);
            $coniguration = file_get_contents("configuration.json");
            $cfg = json_decode($coniguration, true);
            $entity = [];
            if (array_key_exists($ch_id, $locked)) {
                foreach ($cfg['settings_template'] as $key => $value) {
                    if (in_array($key, $locked[$ch_id])) {
                        if (!empty($message)) {
                            $message = $message."Lock ".$cfg['settings_template'][$key].
                            ": <code>Yes</code>\r\n";
                        } else {
                            $message = "<b>Settings for $title:</b>\r\n".
                            "Lock ".$cfg['settings_template'][$key].
                            ": <code>Yes</code>\r\n";
                        }
                    } else {
                        if (!empty($message)) {
                            $message = $message."Lock ".$cfg['settings_template'][$key].
                            ": <code>No</code>\r\n";
                        } else {
                            $message = "<b>Settings for $title:</b>\r\n".
                            "Lock ".$cfg['settings_template'][$key].
                            ": <code>No</code>\r\n";
                        }
                    }
                }
                if (in_array("flood", $locked[$ch_id])) {
                    $message = $message."Floodlimit: <code>".$locked[$ch_id]['floodlimit']."</code>";
                }
            } else {
                $locked[$ch_id] = [];
                file_put_contents('locked.json', json_encode($locked));
                foreach ($cfg['settings_template'] as $key => $value) {
                    if (in_array($key, $locked[$ch_id])) {
                        if (!empty($message)) {
                            $message = $message."Lock ".$cfg['settings_template'][$key].
                            ": <code>Yes</code>\r\n";

                        } else {
                            $message = "<b>Settings for $title:</b>\r\n".
                            "Lock ".$cfg['settings_template'][$key].
                            ": <code>Yes</code>\r\n";
                        }
                    } else {
                        if (!empty($message)) {
                            $message = $message."Lock ".$cfg['settings_template'][$key].
                            ": <code>No</code>\r\n";
                        } else {
                            $message = "<b>Settings for $title:</b>\r\n".
                            "Lock ".$cfg['settings_template'][$key].
                            ": <code>No</code>\r\n";
                        }
                    }
                }
                if (in_array("flood", $locked[$ch_id])) {
                    $message = $message."Floodlimit:<code> ".$locked[$ch_id]['floodlimit']."</code>";
                }
            }
            if (isset($message)) {
                $default['message'] = $message;
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
