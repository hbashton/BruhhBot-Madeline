<?php

function get_settings($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        if (from_admin_mod($update, $MadelineProto)) {
            $title = htmlentities($chat['title']);
            $ch_id = $chat['id'];
            $peer = $chat['peer'];
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
        }
        if (isset($sentMessage)) {
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}

function settings_menu($update, $MadelineProto)
{
    if (bot_present($update, $MadelineProto)) {
        if (is_supergroup($update, $MadelineProto)) {
            $msg_id = $update['update']['message']['id'];
            $mods = $MadelineProto->responses['welcome_toggle']['mods'];
            $chat = parse_chat_data($update, $MadelineProto);
            $title = htmlentities($chat['title']);
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            $userid = cache_from_user_info($update, $MadelineProto);
            if (isset($userid['bot_api_id'])) {
                $userid = $userid['bot_api_id'];
            } else {
                return;
            }
            $default = array(
                'peer' => $peer,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html',
                'message' => "Here's the settings menu! Feel free to explore"
                );
            $rows = [];
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        $buttons = [
                            ['_' => 'keyboardButtonCallback', 'text' => "Locked", 'data' => json_encode(array(
                                "q" => "locked",     // query
                                "u" =>  $userid,     // user
                                "c" =>  $ch_id ))],  // chat
                            ['_' => 'keyboardButtonCallback', 'text' => "Welcome", 'data' => json_encode(array(
                                "q" => "welcome_menu", // query
                                "u" =>  $userid,       // user
                                "c" =>  $ch_id ))]     // chat
                        ];
                        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                        $rows[] = $row;
                    }
                    $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
                    $default['reply_markup'] = $replyInlineMarkup;
                }
            }
            try {
                if (isset($default['message'])) {
                    $sentMessage = $MadelineProto->messages->sendMessage(
                        $default
                    );
                    \danog\MadelineProto\Logger::log($sentMessage);
                }
            } catch (Exception $e) {}
        }
    }
}

function locked_menu($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message' => "Here's the locked settings. Any disabled message type will be deleted when sent.\n\xE2\x9C\x85 => Enabled\n\xE2\x9D\x8C => Disabled"
    );
    if ($parsed_query['data']['u'] != $parsed_query['user_id'] or !is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) return;
    if (is_moderated($ch_id)) {
        $rows = [];
        $file = file_get_contents("locked.json");
        $locked = json_decode($file, true);
        $coniguration = file_get_contents("configuration.json");
        $cfg = json_decode($coniguration, true);
        if (!array_key_exists($ch_id, $locked)) {
            $locked[$ch_id] = [];
        }
        foreach ($cfg['settings_template'] as $key => $value) {
            // check mark \xE2\x9C\x85
            // cross mark \xE2\x9D\x8C
            if (in_array($key, $locked[$ch_id])) {
                $text = "\xE2\x9D\x8C";
                $onoff = "off";
            } else {
                $text = "\xE2\x9C\x85";
                $onoff = "on";
            }
            $buttons = [
                ['_' => 'keyboardButtonCallback', 'text' => $value, 'data' => json_encode(array(
                    "q" => "lock",       // query
                    "v" => "$key-$onoff",// value
                    "u" =>  $userid,     // user
                    "c" =>  $ch_id ))],  // chat
                ['_' => 'keyboardButtonCallback', 'text' => $text, 'data' => json_encode(array(
                    "q" => "lock",       // query
                    "v" => "$key-$onoff",// value
                    "u" =>  $userid,     // user
                    "c" =>  $ch_id ))]   // chat
            ];
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
            $rows[] = $row;
        }
        $buttons = [
                ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
                    "q" => "back_to_settings", // query
                    "u" =>  $userid,           // user
                    "c" =>  $ch_id ))]         // chat
            ];
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
            $rows[] = $row;
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
        $default['reply_markup'] = $replyInlineMarkup;
    }
    try {
        if (isset($default['message'])) {
            try {
                $sentMessage = $MadelineProto->messages->editMessage(
                    $default
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            } catch (Exception $e) {}
        }
    } catch (Exception $e) {}
}

function welcome_menu($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $userid = $parsed_query['user_id'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $parsed_query['msg_id'],
        'parse_mode' => 'html',
        'message' => "If you ask me to, I will greet new people!"
    );
    if ($parsed_query['data']['u'] != $parsed_query['user_id'] or !is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) return;
    if (is_moderated($ch_id)) {
        check_json_array('settings.json', $default['peer']);
        $file = file_get_contents("settings.json");
        $settings = json_decode($file, true);
        
        if (!array_key_exists("welcome", $settings[$default['peer']])) {
            $settings[$default['peer']] = true;
        }
        if ($settings[$default['peer']]["welcome"]) {
            $text = "Welcome new users \xE2\x9C\x85";
        } else {
            $text = "Welcome new users";
        }
        $welcomeon = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "welcome", // query
        "v" => "on",      // value
        "u" =>  $userid,
        "c" =>  $ch_id))]; // userid
        if (!$settings[$default['peer']]["welcome"]) {
            $text = "Don't welcome new users \xE2\x9C\x85";
        } else {
            $text = "Don't welcome new users";
        }
        $welcomeoff = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "welcome",
        "v" => "off",
        "u" =>  $userid,
        "c" =>  $ch_id))];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeon], ];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeoff], ];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
        "q" => "back_to_settings", // query
        "u" =>  $userid,           // user
        "c" =>  $ch_id ))];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back] ];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3], ];
        $default['reply_markup'] = $replyInlineMarkup;
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
        }
    }
}
