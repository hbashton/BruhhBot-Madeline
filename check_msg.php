#!/usr/bin/env php
<?php

class Exec extends Thread {
    public function __construct($command) {
        $this->command = $command;
    }
    public function run() {
        $this->command;
    }
}

function check_locked($update, $MadelineProto) {
    switch ($update['update']['message']['to_id']['_']) {
    case 'peerChannel':
        $peer = $MadelineProto->
        get_info($update['update']['message']['to_id'])
        ['InputPeer'];
        $msg_id = $update['update']['message']['id'];
        $cont = "true";
        break;
    }
    if (isset($cont)) {
        if (is_bot_admin($update, $MadelineProto)) {
            $msg_ = $update["update"]["message"];
            if (array_key_exists("media", $msg_)) {
                switch ($msg_["media"]["_"]) {
                    case 'messageMediaPhoto':
                        $type = "photo";
                        break;
                    case 'messageMediaVideo':
                        $type = "video";
                        break;
                    case 'messageMediaAudio':
                        $type = "audio";
                        break;
                    case 'messageMediaGeo':
                        $type = "geo";
                        break;
                    case 'messageMediaContact':
                        $type = "contact";
                        break;
                    case 'messageMediaDocument':
                        foreach ($msg_["media"]["document"]["attributes"] as $key) {
                            switch ($key["_"]) {
                                case 'documentAttributeSticker':
                                    $type = "sticker";
                                    break 3;
                                case 'documentAttributeAnimated':
                                    $type = "gif";
                                    break 3;
                                case 'documentAttributeVideo':
                                    $type = "video";
                                    break 3;
                                case 'documentAttributeAudio':
                                    $type = "audio";
                                    break 3;
                            }
                        }
                    $type = "document";
                    break;
                }
                if (!empty($type)) {
                    if (!from_admin_mod($update, $MadelineProto)) {
                        $ch_id = -100 . $update['update']['message']['to_id']['channel_id'];
                        if (!file_exists('locked.json')) {
                            $json_data = [];
                            $json_data[$ch_id] = [];
                            file_put_contents('locked.json', json_encode($json_data));
                        }
                        $file = file_get_contents("locked.json");
                        $locked = json_decode($file, true);
                        if (array_key_exists($ch_id, $locked)) {
                            if (in_array($type, $locked[$ch_id])) {
                                $delete = $MadelineProto->channels->deleteMessages
                                (['channel' => $peer, 'id' => [$msg_id] ]);
                                #\danog\MadelineProto\Logger::log($delete);
                                $thred = new Exec($delete);
                                $thred->start();
                                $thred->join();
                            }
                        }
                    }
                }
            }
        }
    }
}
