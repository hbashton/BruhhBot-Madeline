<?php

function wholist($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        if (from_admin($update, $MadelineProto)
            or from_master($update, $MadelineProto)
        ) {
            $message = "Users in $title\r\n";
            $admins = cache_get_chat_info($update, $MadelineProto);
            foreach ($admins['participants'] as $key) {
                if (array_key_exists('user', $key)) {
                    $id = $key['user']['id'];
                    $participant = catch_id($update, $MadelineProto, $id)[2];
                    $message = $message.$participant." $id"."\r\n";
                }
            }
            $filename = "who/who$ch_id";
            check_mkdir("who");
            file_put_contents($filename, $message);
            $inputFile = $MadelineProto->upload($filename, 'wholist');
            $inputMedia = [
                '_' => 'inputMediaUploadedDocument',
                'file' => $inputFile,
                'mime_type' => 'magic/magic',
                'caption' => "List of participants for $title",
                'attributes' => [[
                    '_' => 'documentAttributeFilename',
                    'file_name' => 'wholist.txt'
                    ]]
                ];
            $sentMedia = $MadelineProto->messages->sendMedia(
                ['peer' => $peer,
                'media' => $inputMedia]
            );
            if (isset($sentMedia)) {
                \danog\MadelineProto\Logger::log($sentMedia);
            }
        }
    }
}

function whofile($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $message = [];
        if (from_admin($update, $MadelineProto)
            or from_master($update, $MadelineProto)
        ) {
            $users = cache_get_chat_info($update, $MadelineProto);
            foreach ($users['participants'] as $key) {
                if (array_key_exists('user', $key)) {
                    $id = $key['user']['id'];
                    $participant = catch_id($update, $MadelineProto, $id)[2];
                    $message[$id] = $participant;
                }
            }
            $filename = "who/whofile$ch_id";
            check_mkdir("who");
            file_put_contents($filename, json_encode($message));
            $inputFile = $MadelineProto->upload($filename, 'whofile');
            $inputMedia = [
                '_' => 'inputMediaUploadedDocument',
                'file' => $inputFile,
                'mime_type' => 'magic/magic',
                'caption' => "",
                'attributes' => [[
                    '_' => 'documentAttributeFilename',
                    'file_name' => 'whofile.txt'
                    ]]
                ];
            $sentMedia = $MadelineProto->messages->sendMedia(
                ['peer' => $peer,
                'media' => $inputMedia]
            );
            if (isset($sentMedia)) {
                \danog\MadelineProto\Logger::log($sentMedia);
            }
        }
    }
}

function whoban($update, $MadelineProto, $wait = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_master(
                $update,
                $MadelineProto
            )
            ) {
                if (isset($MadelineProto->wait_for_whoban)) {
                    if ($MadelineProto->wait_for_whoban == $fromid) {
                        if (array_key_exists(
                            "media",
                            $update['update']['message']
                        )
                        ) {
                            $mediatype = $update['update']['message']['media']['_'];
                            if ($mediatype == "messageMediaDocument") {
                                $hash = $update
                                ['update']['message']['media']['document']
                                ['access_hash'];
                                $id = $update
                                ['update']['message']['media']['document']['id'];
                                $ver = $update
                                ['update']['message']['media']['document']
                                ['version'];
                                $Document = $update
                                ['update']['message']['media'];
                                try {
                                    $output_file_name =
                                    $MadelineProto->download_to_dir(
                                        $Document,
                                        "who"
                                    );
                                    \danog\MadelineProto\Logger::log(
                                        $output_file_name
                                    );
                                    $message = "I'm on it! Banning as we speak";
                                    $file = file_get_contents(
                                        $output_file_name
                                    );
                                    $whobantxt = json_decode($file, true);
                                    var_dump($whobantxt, true);
                                    foreach ($whobantxt as $key => $value) {
                                        banme($update, $MadelineProto, $key, false);
                                    }
                                    $default['message'] = $message;

                                } catch (Exception $e) {
                                    $message = "something went horribly wrong ^.^";
                                    $default['message'] = $message;
                                }
                                unset($MadelineProto->wait_for_whoban);
                            } else {
                                $message = "The message you sent was not a".
                                "document! Try again ";
                                $default['message'] = $message;
                                unset($MadelineProto->wait_for_whoban);
                            }
                        } else {
                            $message = "The message you sent was not a document!".
                            " Try again ";
                            $default['message'] = $message;
                            unset($MadelineProto->wait_for_whoban);
                        }
                    }
                } else {
                    $MadelineProto->wait_for_whoban = $fromid;
                    $message = "Just send the /whofile and I'll ".
                    "bring down the banhammer";
                    $default['message'] = $message;
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}

function whobanall($update, $MadelineProto, $wait = true)
{
    if (is_supergroup($update, $MadelineProto)) {
        $msg_id = $update['update']['message']['id'];
        $chat = parse_chat_data($update, $MadelineProto);
        $peer = $chat['peer'];
        $title = htmlentities($chat['title']);
        $ch_id = $chat['id'];
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        $default = array(
            'peer' => $peer,
            'reply_to_msg_id' => $msg_id,
            );
        $fromid = cache_from_user_info($update, $MadelineProto)['bot_api_id'];
        if (is_bot_admin($update, $MadelineProto)) {
            if (from_master(
                $update,
                $MadelineProto
            )
            ) {
                if (isset($MadelineProto->wait_for_whobanall)) {
                    if ($MadelineProto->wait_for_whobanall == $fromid) {
                        if (array_key_exists(
                            "media",
                            $update['update']['message']
                        )
                        ) {
                            $mediatype = $update['update']['message']['media']['_'];
                            if ($mediatype == "messageMediaDocument") {
                                $hash = $update
                                ['update']['message']['media']['document']
                                ['access_hash'];
                                $id = $update
                                ['update']['message']['media']['document']['id'];
                                $ver = $update
                                ['update']['message']['media']['document']
                                ['version'];
                                $Document = $update
                                ['update']['message']['media'];
                                try {
                                    $output_file_name =
                                    $MadelineProto->download_to_dir(
                                        $Document,
                                        "who"
                                    );
                                    \danog\MadelineProto\Logger::log(
                                        $output_file_name
                                    );
                                    $message = "I'm on it! Banning as we speak";
                                    $default['message'] = $message;
                                    $sentMessage = $MadelineProto->messages->sendMessage(
                                        $default
                                    );
                                    $file = file_get_contents(
                                        $output_file_name
                                    );
                                    $whobantxt = json_decode($file, true);
                                    var_dump($whobantxt);
                                    foreach ($whobantxt as $key => $value) {
                                        banall($update, $MadelineProto, $key, false, false, true);
                                    }
                                    $message = "ALL DONE!";
                                    $default['message'] = $message;

                                } catch (Exception $e) {
                                    $message = "something went horribly wrong ^.^";
                                    $default['message'] = $message;
                                }
                                unset($MadelineProto->wait_for_whobanall);
                            } else {
                                $message = "The message you sent was not a".
                                "document! Try again ";
                                $default['message'] = $message;
                                unset($MadelineProto->wait_for_whobanall);
                            }
                        } else {
                            $message = "The message you sent was not a document! ".
                            " Try again ";
                            $default['message'] = $message;
                            unset($MadelineProto->wait_for_whobanall);
                        }
                    }
                } else {
                    $MadelineProto->wait_for_whobanall = $fromid;
                    $message = "Just send the /whofile and I'll ".
                    "bring down the banhammer";
                    $default['message'] = $message;
                }
            }
        }
        if (isset($default['message'])) {
            $sentMessage = $MadelineProto->messages->sendMessage(
                $default
            );
            if (isset($sentMessage)) {
                \danog\MadelineProto\Logger::log($sentMessage);
            }
        }
    }
}
