<?php

function check_json_array($filename, $id = "", $layered = true)
{
    if ($layered) {
        if (!file_exists($filename)) {
            $json_data = [];
            $json_data[$id] = [];
            file_put_contents(
                $filename,
                json_encode($json_data)
            );
        }
    } else {
        if (!file_exists($filename)) {
            $json_data = [];
            file_put_contents(
                $filename,
                json_encode($json_data)
            );
        }
    }
}

function check_mkdir($foldername)
{
    if (!file_exists($foldername)) {
        mkdir($foldername, 0700);
    }
}

function is_moderated($ch_id)
{
    check_json_array('chatlist.json', $ch_id, false);
    $file = file_get_contents("chatlist.json");
    $chatlist = json_decode($file, true);
    if (in_array($ch_id, $chatlist)) {
        return true;
    } else {
        return false;
    }
}

function is_supergroup($update, $MadelineProto)
{
    if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
        return true;
    } else {
        return false;
    }
}

function is_peeruser($update, $MadelineProto)
{
    if ($update['update']['message']['to_id']['_'] == "peerUser") {
        return true;
    } else {
        return false;
    }
}

function parse_chat_data($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $info = cache_get_chat_info(
            $update,
            $MadelineProto,
            -100 . $update['update']['message']['to_id']['channel_id']
        );
        $peer = $info['id'];
        $title = $info['title'];
        $ch_id = $info['id'];
        $chat_array = array(
            'peer' => $peer,
            'title' => $title,
            'id' => $ch_id);
        return($chat_array);
    }
}

function user_specific_data($update, $MadelineProto, $user)
{
    /**
    if (array_key_exists("fwd_from", $update['update']['message'])) {
        $info = cache_get_info(
            $update,
            $MadelineProto,
            $update['update']['message']['fwd_from']['from_id']
        );
        **/
    $id = catch_id($update, $MadelineProto, $user);
    if ($id[0]) {
        $info = cache_get_info($update, $MadelineProto, $id[1])['User'];
        $userid = $info['id'];
        $firstname = $info['first_name'];
        if (array_key_exists('last_name', $info)) {
            $lastname = $info['last_name'];
        }
        if (array_key_exists('username', $info)) {
            $username = $info['username'];
        }
        if (is_gbanned($update, $MadelineProto, $userid)) {
            $gbanned = true;
        }
        $isbanned = is_banned_anywhere($update, $MadelineProto, $userid);
        if ($isbanned[0]) {
            var_dump($isbanned, true);
            unset($isbanned[0]);
            foreach ($isbanned as $key => $value) {
                $chat = cache_get_info($update, $MadelineProto, $value);
                $title = $chat["Chat"]["title"];
                $id = $chat['Chat']['id'];
                if (!is_null($title) && !is_null($id)) {
                    $title_id = array(
                        'title' => $title,
                        'id' => $id);
                    if (!isset($ban_array)) {
                        $ban_array = [];
                        $ban_array[] = $title_id;
                    } else {
                        $ban_array[] = $title_id;
                    }
                }
            }
        }
        $fwd_array = array(
            'id' => $userid,
            'firstname' => $firstname);

        if (isset($lastname)) {
            $fwd_array['lastname'] = $lastname;
        }
        if (isset($username)) {
            $fwd_array['username'] = $username;
        }
        if (isset($ban_array)) {
            $fwd_array['banned'] = $ban_array;
        }
        if (isset($gbanned)) {
            $fwd_array['gbanned'] = $gbanned;
        }
        var_dump($fwd_array);
        return($fwd_array);
    } else {
        return false;
    }
}

function is_gbanned($update, $MadelineProto, $user) {
    check_json_array('gbanlist.json', false, false);
    $file = file_get_contents("gbanlist.json");
    $gbanlist = json_decode($file, true);
    $id = catch_id($update, $MadelineProto, $user);
    if ($id[0]) {
        $userid = $id[1];
        if (in_array($userid, $gbanlist)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function is_banned_anywhere($update, $MadelineProto, $user) {
    check_json_array('banlist.json', false, false);
    $file = file_get_contents("banlist.json");
    $banlist = json_decode($file, true);
    $id = catch_id($update, $MadelineProto, $user);
    if ($id[0]) {
        $userid = $id[1];
        foreach ($banlist as $key => $value) {
            if (in_array($userid, $value)) {
                if (!isset($chats)) {
                    $chats = [true];
                    $chats[] = $key;
                } else {
                    $chats[] = $key;
                }
            }
        }
        if (!isset($chats)) {
            $chats = [false];
        }
        var_dump($chats);
        return($chats);
    } else {
        return false;
    }
}

function create_style($type, $offset, $length, $full = true)
{
    switch ($type) {
    case 'bold':
        $style = 'messageEntityBold';
        break;
    case 'italic':
        $style = 'messageEntityItalic';
        break;
    case 'code':
        $style = 'messageEntityCode';
        break;
    }
    if (!is_numeric($length)) {
        $length = strlen($length);
    }
    if ($full) {
        return([['_' => $style, 'offset' => $offset,
                'length' => $length ]]);
    } else {
        return(['_' => $style, 'offset' => $offset,
                'length' => $length ]);
    }
}

function create_mention($offset, $username, $userid, $full = true)
{
    if ($full) {
        return([[
            '_' => 'inputMessageEntityMentionName',
            'offset' => $offset,
            'length' => strlen($username),
            'user_id' => $userid]]);
    } else {
        return([
            '_' => 'inputMessageEntityMentionName',
            'offset' => $offset,
            'length' => strlen($username),
            'user_id' => $userid]);
    }
}

function html_mention($username, $userid) {
    $mention = "<a href=\"mention:$userid\">$username</a>";
    return($mention);
}

function html_bold($text) {
    $bold = "<b>$text</b>";
    return($bold);
}

class Template_String {

    public static function sprintf($format, array $args = array()) {
        $arg_nums = array_slice(array_flip(array_keys(array(0 => 0) + $args)), 1);

        for ($pos = 0; preg_match('/(?<=%)\(([a-zA-Z_][\w\s]*)\)/', $format, $match, PREG_OFFSET_CAPTURE, $pos);) {
            $arg_pos = $match[0][1];
            $arg_len = strlen($match[0][0]);
            $arg_key = $match[1][0];

            if (! array_key_exists($arg_key, $arg_nums)) {
                user_error("sprintfn(): Missing argument '${arg_key}'", E_USER_WARNING);
                return false;
            }
            $format = substr_replace($format, $replace = $arg_nums[$arg_key] . '$', $arg_pos, $arg_len);
            $pos = $arg_pos + strlen($replace); // skip to end of replacement for next iteration
        }

        return vsprintf($format, array_values($args));
    }
}

function cb($content){

  if(!mb_check_encoding($content, 'UTF-8')
   OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {

    $content = mb_convert_encoding($content, 'UTF-8');

  }
  return $content;
}
