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
                if (isset($locked[$ch_id])) {
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
            $title = $chat['title'];
            $peer = $chat['peer'];
            $ch_id = $chat['id'];
            $userid = cache_from_user_info($update, $MadelineProto);
            if (isset($userid['bot_api_id'])) {
                $userid = $userid['bot_api_id'];
            } else {
                return;
            }
            $default = array(
                'peer' => $userid,
                'reply_to_msg_id' => $msg_id,
                'parse_mode' => 'html',
                'message' => "Here's the settings menu for $title! Feel free to explore"
                );
            $rows = [];
            if (is_moderated($ch_id)) {
                if (is_bot_admin($update, $MadelineProto)) {
                    if (from_admin_mod($update, $MadelineProto, $mods, true)) {
                        $buttons = [
                            ['_' => 'keyboardButtonCallback', 'text' => "Locked", 'data' => json_encode(array(
                                "q" => "locked",     // query
                                "c" =>  $ch_id ))],  // chat
                            ['_' => 'keyboardButtonCallback', 'text' => "Welcome", 'data' => json_encode(array(
                                "q" => "welcome_menu", // query
                                "c" =>  $ch_id ))]     // chat
                        ];
                        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                        $rows[] = $row;
                        $button = [
                            ['_' => 'keyboardButtonCallback', 'text' => "User Settings", 'data' => json_encode(array(
                                "q" => "user_settings",
                                "c" =>  $ch_id))]
                        ];
                        $row = ['_' => 'keyboardButtonRow', 'buttons' => $button ];
                        $rows[] = $row;
                        if (is_chat_owner($update, $MadelineProto, $ch_id, $userid)) {
                            $buttons = [
                                ['_' => 'keyboardButtonCallback', 'text' => "Moderators", 'data' => json_encode(array(
                                    "q" => "moderators_menu",
                                    "c" =>  $ch_id ))]
                            ];
                            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                            $rows[] = $row;
                        }
                        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
                        $default['reply_markup'] = $replyInlineMarkup;
                        try {
                            if (isset($default['message'])) {
                                $sentMessage = $MadelineProto->messages->sendMessage(
                                    $default
                                );
                                \danog\MadelineProto\Logger::log($sentMessage);
                            }
                        } catch (Exception $e) {
                            $default['peer'] = $peer;
                            $botusername = preg_replace("/@/", "",getenv("BOT_API_USERNAME"));
                            $url = "https://telegram.me/$botusername?start=settings-$ch_id";
                            $keyboardButtonUrl = ['_' => 'keyboardButtonUrl', 'text' => "Start a chat with me!", 'url' => $url, ];
                            $buttons = [$keyboardButtonUrl];
                            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
                            $rows = [$row];
                            $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
                            $default['reply_markup'] = $replyInlineMarkup;
                            $default['message'] = "Please start a chat with me so I can send you the settings for $title";
                            $sentMessage = $MadelineProto->messages->sendMessage(
                                    $default
                                );
                            \danog\MadelineProto\Logger::log($sentMessage);
                        }
                    }
                }
            }
        }
    }
}

function settings_menu_deeplink($update, $MadelineProto, $ch_id)
{
    $msg_id = $update['update']['message']['id'];
    $info = cache_get_info($update, $MadelineProto, $ch_id, true);
    if ($info) {
        $title = "for ".$info['title'];
    } else {
        $title = "";
    }
    $userid = cache_from_user_info($update, $MadelineProto);
    if (isset($userid['bot_api_id'])) {
        $userid = $userid['bot_api_id'];
    } else {
        return;
    }
    $default = array(
        'peer' => $userid,
        'reply_to_msg_id' => $msg_id,
        'parse_mode' => 'html',
        'message' => "Here's the settings menu for $title! Feel free to explore"
        );
    $rows = [];
    if (!is_admin_mod($update, $MadelineProto, $userid, false, false, $ch_id)) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => "You cannot change the settings of this chat", 'cache_time' => 3, ]);
            \danog\MadelineProto\Logger::log($callbackAnswer);
        } catch (Exception $e) {}
        return;
    }
    if (is_moderated($ch_id)) {
        $buttons = [
            ['_' => 'keyboardButtonCallback', 'text' => "Locked", 'data' => json_encode(array(
                "q" => "locked",
                "c" =>  $ch_id ))],
            ['_' => 'keyboardButtonCallback', 'text' => "Welcome", 'data' => json_encode(array(
                "q" => "welcome_menu",
                "c" =>  $ch_id ))]
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
        $button = [
            ['_' => 'keyboardButtonCallback', 'text' => "User Settings", 'data' => json_encode(array(
                "q" => "user_settings",
                "c" =>  $ch_id))]
        ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $button ];
        $rows[] = $row;
        if (is_chat_owner($update, $MadelineProto, $ch_id, $userid)) {
            $buttons = [
                ['_' => 'keyboardButtonCallback', 'text' => "Moderators", 'data' => json_encode(array(
                    "q" => "moderators_menu",
                    "c" =>  $ch_id ))]
            ];
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
            $rows[] = $row;
        }
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
        $default['reply_markup'] = $replyInlineMarkup;
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

function locked_menu($update, $MadelineProto)
{
    $parsed_query = parse_query($update, $MadelineProto);
    $peer = $parsed_query['peer'];
    $id = $parsed_query['msg_id'];
    $ch_id = $parsed_query['data']['c'];
    $info = cache_get_info($update, $MadelineProto, $ch_id, true);
    if ($info) {
        $title = "for ".$info['title'];
    } else {
        $title = "";
    }
    $userid = $parsed_query['user_id'];
    $default = array(
        'peer' => $parsed_query['peer'],
        'id' => $id,
        'parse_mode' => 'html',
        'message' => "Here's the locked settings $title. Any disabled message type will be deleted when sent.\n\xE2\x9C\x85 => Enabled\n\xE2\x9D\x8C => Disabled"
    );
    if (!is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => "You cannot change the settings of this chat", 'cache_time' => 3, ]);
            \danog\MadelineProto\Logger::log($callbackAnswer);
        } catch (Exception $e) {}
        return;
    }
    if (is_moderated($ch_id)) { 
        $rows = [];
        $file = file_get_contents("locked.json");
        $locked = json_decode($file, true);
        $coniguration = file_get_contents("configuration.json");
        $cfg = json_decode($coniguration, true);
        if (!isset($locked[$ch_id])) {
            $locked[$ch_id] = [];
            file_put_contents('locked.json', json_encode($locked));
        }
        if (!isset($locked[$ch_id]["floodlimit"])) {
            $locked[$ch_id]["floodlimit"] = 10;
            file_put_contents('locked.json', json_encode($locked));
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
                    "q" => "hint",       // query
                    "v" => "$key"))],
                ['_' => 'keyboardButtonCallback', 'text' => $text, 'data' => json_encode(array(
                    "q" => "lock",       // query
                    "v" => "$key-$onoff",// value
                    "c" =>  $ch_id ))]   // chat
            ];
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
            $rows[] = $row;
        }
        $buttons = [
                ['_' => 'keyboardButtonCallback', 'text' => "\xe2\xac\x85\xef\xb8\x8f", 'data' => json_encode(array(
                    "q" => "decrease_flood",   // query
                    "c" =>  $ch_id ))],        // chat
                ['_' => 'keyboardButtonCallback', 'text' => (string) $locked[$ch_id]['floodlimit'], 'data' => json_encode(array(
                    "q" =>  "hint",            // query
                    "v" =>  "flood",           // value
                    "c" =>  $ch_id ))],        // chat
                ['_' => 'keyboardButtonCallback', 'text' => "\xe2\x9e\xa1\xef\xb8\x8f", 'data' => json_encode(array(
                    "q" => "increase_flood",   // query
                    "c" =>  $ch_id ))],        // chat
            ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
        $buttons = [
                ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
                    "q" => "back_to_settings", // query
                    "c" =>  $ch_id ))]         // chat
            ];
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => $rows, ];
        $default['reply_markup'] = $replyInlineMarkup;
    }
    try {
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->editMessage(
                $default
            );
            \danog\MadelineProto\Logger::log($sentMessage);
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
    if (!is_admin_mod($update, $MadelineProto, $parsed_query['user_id'], false, false, $parsed_query['data']['c'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => "You cannot change the settings of this chat", 'cache_time' => 3, ]);
            \danog\MadelineProto\Logger::log($callbackAnswer);
        } catch (Exception $e) {}
        return;
    }
    if (is_moderated($ch_id)) {
        check_json_array('settings.json', $ch_id);
        $file = file_get_contents("settings.json");
        $settings = json_decode($file, true);
        if (!isset($settings[$ch_id])) {
            $settings[$ch_id] = [];
        }
        if (!array_key_exists("welcome", $settings[$ch_id])) {
            $settings[$ch_id]['welcome']  = true;
        }
        if ($settings[$ch_id]["welcome"]) {
            $text = "Welcome new users \xE2\x9C\x85";
        } else {
            $text = "Welcome new users";
        }
        $welcomeon = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "welcome", // query
        "v" => "on",      // value
        "c" =>  $ch_id))]; // userid
        if (!$settings[$ch_id]["welcome"]) {
            $text = "Don't welcome new users \xE2\x9C\x85";
        } else {
            $text = "Don't welcome new users";
        }
        $welcomeoff = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "welcome",
        "v" => "off",
        "c" =>  $ch_id))];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeon], ];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$welcomeoff], ];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
        "q" => "back_to_settings", // query
        "c" =>  $ch_id ))];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back] ];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3], ];
        $default['reply_markup'] = $replyInlineMarkup;
        if (isset($default['message'])) {
            try {
                $sentMessage = $MadelineProto->messages->editMessage(
                    $default
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            } catch (Exception $e) {}
        }
    }
}

function moderators_menu($update, $MadelineProto)
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
        'message' => "Moderators can be restricted so that their messages are limited just like a regular user. As the owner of the group, you can configure that setting here."
    );
    if (!is_chat_owner($update, $MadelineProto, $parsed_query['data']['c'], $parsed_query['user_id'])) {
        try {
            $callbackAnswer = $MadelineProto->messages->setBotCallbackAnswer(['alert'  => true, 'query_id' => $parsed_query['query_id'], 'message' => "You cannot change the moderation settings of this chat", 'cache_time' => 3, ]);
            \danog\MadelineProto\Logger::log($callbackAnswer);
        } catch (Exception $e) {}
        return;
    }
    if (is_moderated($ch_id)) {
        check_json_array('settings.json', $ch_id);
        $file = file_get_contents("settings.json");
        $settings = json_decode($file, true);
        if (!isset($settings[$ch_id])) {
            $settings[$ch_id] = [];
        }
        if (!array_key_exists("restrict_mods", $settings[$ch_id])) {
            $settings[$ch_id]['restrict_mods']  = false;
        }
        if ($settings[$ch_id]["restrict_mods"]) {
            $text = "Limit moderators \xE2\x9C\x85";
        } else {
            $text = "Limit moderators";
        }
        $limiton = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "moderators", // query
        "v" => "on",      // value
        "c" =>  $ch_id))]; // userid
        if (!$settings[$ch_id]["restrict_mods"]) {
            $text = "Don't limit moderators \xE2\x9C\x85";
        } else {
            $text = "Don't limit moderators";
        }
        $limitoff = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "moderators",
        "v" => "off",
        "c" =>  $ch_id))];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$limiton], ];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$limitoff], ];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
        "q" => "back_to_settings", // query
        "c" =>  $ch_id ))];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back] ];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3], ];
        $default['reply_markup'] = $replyInlineMarkup;
        if (isset($default['message'])) {
            try {
                $sentMessage = $MadelineProto->messages->editMessage(
                    $default
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            } catch (Exception $e) {}
        }
    }
}

function alert_me_menu($update, $MadelineProto)
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
        'message' => "If you are a moderator in this group you may recieve alerts about actions taken. You can opt to recieve them, or not, here."
    );
    if (is_moderated($ch_id)) {
        check_json_array('settings.json', $ch_id);
        $file = file_get_contents("settings.json");
        $settings = json_decode($file, true);
        if (!isset($settings[$ch_id])) {
            $settings[$ch_id] = [];
        }
        if (!isset($settings[$ch_id][$userid])) {
            $settings[$ch_id][$userid] = [];
        }
        if (!isset($settings[$ch_id][$userid]["alertme"])) {
            $settings[$ch_id][$userid]["alertme"] = true;
        }
        if ($settings[$ch_id][$userid]["alertme"]) {
            $text = "Alert me! \xE2\x9C\x85";
        } else {
            $text = "Alert me.";
        }
        $on = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "alert_me_cb", // query
        "v" => "on",      // value
        "c" =>  $ch_id))]; // userid
        if (!$settings[$ch_id][$userid]["alertme"]) {
            $text = "Don't alert me! \xE2\x9C\x85";
        } else {
            $text = "Don't alert me.";
        }
        $off = ['_' => 'keyboardButtonCallback', 'text' => "$text", 'data' => json_encode(array(
        "q" => "alert_me_cb",
        "v" => "off",
        "c" =>  $ch_id))];
        $row1 = ['_' => 'keyboardButtonRow', 'buttons' => [$on], ];
        $row2 = ['_' => 'keyboardButtonRow', 'buttons' => [$off], ];
        $back = ['_' => 'keyboardButtonCallback', 'text' => "\xf0\x9f\x94\x99", 'data' => json_encode(array(
        "q" => "back_to_settings", // query
        "c" =>  $ch_id ))];        // chat
        $row3 = ['_' => 'keyboardButtonRow', 'buttons' => [$back] ];
        $replyInlineMarkup = ['_' => 'replyInlineMarkup', 'rows' => [$row1, $row2, $row3], ];
        $default['reply_markup'] = $replyInlineMarkup;
        if (isset($default['message'])) {
            try {
                $sentMessage = $MadelineProto->messages->editMessage(
                    $default
                );
                \danog\MadelineProto\Logger::log($sentMessage);
            } catch (Exception $e) {}
        }
    }
}
