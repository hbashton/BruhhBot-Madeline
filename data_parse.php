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
    if ($update['update']['_'] == "updateNewChannelMessage" or $update['update']['_'] == "updateNewMessage") {
        if ($update['update']['message']['to_id']['_'] == 'peerChannel') {
            return true;
        } else {
            return false;
        }
    }
    if ($update['update']['_'] == "updateBotCallbackQuery") {
        if ($update['update']['peer']['_'] == 'peerChannel') {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

function is_peeruser($update, $MadelineProto)
{
    if ($update['update']['_'] == "updateNewChannelMessage" or $update['update']['_'] == "updateNewMessage") {
        if ($update['update']['message']['to_id']['_'] == 'peerUser') {
            return true;
        } else {
            return false;
        }
    }
    if ($update['update']['_'] == "updateBotCallbackQuery") {
        if ($update['update']['peer']['_'] == 'peerUser') {
            return true;
        } else {
            return false;
        }
    }
    return false;
}

function parse_chat_data($update, $MadelineProto)
{
    if (is_supergroup($update, $MadelineProto)) {
        $info = cache_get_chat_info(
            $update,
            $MadelineProto,
            -100 . $update['update']['message']['to_id']['channel_id']
        );

if (!isset($info['id'])) var_dump($info);
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

function parse_query($update, $MadelineProto)
{
    if ($update['update']['_'] == "updateBotCallbackQuery") {
        if ($update['update']['peer']['_'] == 'peerUser') {
            $peer = $update['update']['peer']['user_id'];
        }
        if ($update['update']['peer']['_'] == 'peerChannel') {
            $peer = -100 . $update['update']['peer']['channel_id'];
        }
        $callback_data = json_decode($update['update']['data'], true);
        $parsed_query = array(
            "peer" => $peer,
            "data" => $callback_data,
            "msg_id" => $update['update']['msg_id'],
            "user_id" => $update['update']['user_id'],
            "query_id" => $update['update']['query_id'],
            "instance" => $update['update']['chat_instance']
        );
        return $parsed_query;
    } else {
        return false;
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
            unset($isbanned[0]);
            foreach ($isbanned as $key => $value) {
                $chat = cache_get_info($update, $MadelineProto, $value);
                $title = htmlentities($chat["Chat"]["title"]);
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
        return($fwd_array);
    } else {
        return false;
    }
}

function is_gbanned($update, $MadelineProto, $user)
{
    check_json_array('gbanlist.json', false, false);
    $file = file_get_contents("gbanlist.json");
    $gbanlist = json_decode($file, true);
    $id = catch_id($update, $MadelineProto, $user);
    if ($id[0]) {
        $userid = $id[1];
        if (array_key_exists($userid, $gbanlist)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function is_banned_anywhere($update, $MadelineProto, $user)
{
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
        $length = mb_strlen($length);
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

function html_mention($username, $userid)
{
    $username = htmlentities($username);
    $mention = "<a href=\"mention:$userid\">$username</a>";
    return($mention);
}

function html_bold($text)
{
    $text = htmlentities($text);
    $bold = "<b>$text</b>";
    return($bold);
}

function markdown($text, $style)
{
    $text = htmlentities($text);
    switch ($style) {
    case 'bold':
        $text = "*$text*";
    break;
    case 'italic':
        $text = "_$text_";
    break;
    case 'code':
        $text = "```$text```";
    break;
    }
    return($text);
}

class Template_String
{

    public static function sprintf($format, array $args = array())
    {
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

function cb($content)
{

    if (!mb_check_encoding($content, 'UTF-8')
        OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'))
    ) {

        $content = mb_convert_encoding($content, 'UTF-8');

    }
    return $content;
}

function split_to_chunks($text){
	$total_length = 4000;
	$text_arr = multipleExplodeKeepDelimiters(array("\n"),$text);
	$i=0;
	$message[0]="";
	foreach ($text_arr as $word){
		if ( strlen($message[$i] . $word . ' ') <= $total_length ){
			if ($text_arr[count($text_arr)-1] == $word){
				$message[$i] .= $word;
			} else {
				$message[$i] .= $word . ' ';
			}
		} else {
			$i++;
			if ($text_arr[count($text_arr)-1] == $word){
				$message[$i] = $word;
			} else {
				$message[$i] = $word . ' ';
			}
		}
	}
	return $message;
}

function multipleExplodeKeepDelimiters($delimiters, $string) {
    $initialArray = explode(chr(1), str_replace($delimiters, chr(1), $string));
    $finalArray = array();
    foreach($initialArray as $item) {
        if(strlen($item) > 0) array_push($finalArray, $item . $string[strpos($string, $item) + strlen($item)]);
    }
    return $finalArray;
}

//function fixtags($text)
//{
//    preg_match_all("#(.*?)(<(a|b|strong|em|i|code|pre)[^>]*>)(.*?)(<\/\\3>)(.*)?#is", $text, $matches, PREG_SET_ORDER);
//    if ($matches) {
//        $last = count($matches) - 1;
//        foreach ($matches as $val) {
//            if (trim($val[1]) != '') {
//                $text = str_replace($val[1], htmlentities($val[1]), $text);
//            }
//            $text = str_replace($val[4], htmlentities(trim($val[4])), $text);
//            if ($val == $matches[$last]) {
//                $text = str_replace($val[6], fixtags($val[6]), $text);
//            }
//        }
//        preg_match_all("#<a href=\x22(.+?)\x22>#is", $text, $matches);
//        foreach ($matches[1] as $match) {
//            $text = str_replace($match, htmlentities($match), $text);
//        }
//
//        return($text);
//    } else {
//        return(htmlentities($text));
//    }
//}

function decodeEmoticons($src)
{
    $replaced = preg_replace("/\\\\u([0-9A-F]{1,4})/i", "&#x$1;", $src);
    $result = mb_convert_encoding($replaced, "UTF-16", "HTML-ENTITIES");
    $result = mb_convert_encoding($result, 'utf-8', 'utf-16');
    return $result;
}

function build_keyboard_callback($button_list, $count = 2, $header = false, $footer = false, $end = false)
{
    $rows =[];
    $buttons = [];
    $cols = 0;
    if(count($button_list)%$count != 0) {
      $end = true;
    }
    foreach ($button_list as $button) {
         if ($cols < $count) {
             $buttons[] = $button;
             $cols++;
         } else {
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
            $rows[] = $row;
            $buttons = [];
            $buttons[] = $button;
            $cols = 1;
        }
    }
    if ($end or $cols == $count) {
        $row = ['_' => 'keyboardButtonRow', 'buttons' => $buttons ];
        $rows[] = $row;
    }
    if ($header) {
        try {
        var_dump($header);
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $header ];
            array_unshift($rows, $row);
        } catch (Exception $e) {}
    }
    if ($footer) {
        try {
            $row = ['_' => 'keyboardButtonRow', 'buttons' => $footer ];
            array_push($rows, $row);
        } catch (Exception $e) {}
    }
    return($rows);
}
