<?php

function get_settings($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
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
                            ": Yes\r\n";
                            $len = strlen($message) - 5;
                            $entity[] = create_style('code', $len, 3, false);
                        } else {
                            $message = "Lock ".$cfg['settings_template'][$key].
                            ": Yes\r\n";
                            $len = strlen($message) - 5;
                            $entity[] = create_style('code', $len, 3, false);
                        }
                    } else {
                        if (!empty($message)) {
                            $message = $message."Lock ".$cfg['settings_template'][$key].
                            ": No\r\n";
                            $len = strlen($message) - 5;
                            $entity[] = create_style('code', $len, 3, false);
                        } else {
                            $message = "Lock ".$cfg['settings_template'][$key].
                            ": No\r\n";
                            $len = strlen($message) - 5;
                            $entity[] = create_style('code', $len, 3, false);
                        }
                    }
                }
                if (in_array("flood", $locked[$ch_id])) {
                    $message = $message."Floodlimit: ".$locked[$ch_id]['floodlimit'];
                    $len = strlen($message) - strlen($locked[$ch_id]['floodlimit']);
                    $len2 = strlen($locked[$ch_id]['floodlimit']);
                    $entity[] = create_style('code', $len, $len2, false);
                }
            } else {
                $locked[$ch_id] = [];
                file_put_contents('locked.json', json_encode($locked));
                foreach ($cfg['settings_template'] as $key => $value) {
                    if (in_array($key, $locked[$ch_id])) {
                        if (!empty($message)) {
                            $message = $message."Lock ".$cfg['settings_template'][$key].
                            ": Yes\r\n";
                            $len = strlen($message) - 4;
                            $entity[] = create_style('code', $len, 3, false);

                        } else {
                            $message = "Lock ".$cfg['settings_template'][$key].
                            ": Yes\r\n";
                            $len = strlen($message) - 4;
                            $entity[] = create_style('code', $len, 3, false);
                        }
                    } else {
                        if (!empty($message)) {
                            $message = $message."Lock ".$cfg['settings_template'][$key].
                            ": No\r\n";
                            $len = strlen($message) - 3;
                            $entity[] = create_style('code', $len, 2, false);
                        } else {
                            $message = "Lock ".$cfg['settings_template'][$key].
                            ": No\r\n";
                            $len = strlen($message) - 3;
                            $entity[] = create_style('code', $len, 2, false);
                        }
                    }
                }
                if (in_array("flood", $locked[$ch_id])) {
                    $message = $message."Floodlimit: ".$locked[$ch_id]['floodlimit'];
                    $len = strlen($message) - strlen($locked[$ch_id]['floodlimit']);
                    $len2 = strlen($locked[$ch_id]['floodlimit']);
                    $entity[] = create_style('code', $len, $len2, false);
                }
            }
            if (isset($entity)) {
                $default['message'] = $message;
                $default['entities'] = $entity;
                $sentMessage = $MadelineProto->messages->sendMessage(
                    $default
                );
            } else {
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