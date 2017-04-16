<?php

function check_utf8($str) {
    $len = strlen($str);
    for($i = 0; $i < $len; $i++){
        $c = ord($str[$i]);
        if ($c > 128) {
            if (($c > 247)) return false;
            elseif ($c > 239) $bytes = 4;
            elseif ($c > 223) $bytes = 3;
            elseif ($c > 191) $bytes = 2;
            else return false;
            if (($i + $bytes) > $len) return false;
            while ($bytes > 1) {
                $i++;
                $b = ord($str[$i]);
                if ($b < 128 || $b > 191) return false;
                $bytes--;
            }
        }
    }
    return true;
} // end of check_utf8

function uniord($u) {
    // i just copied this function fron the php.net comments, but it should work fine!
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1;
}
function is_arabic($str) {
    if(mb_detect_encoding($str) !== 'UTF-8') {
        $str = mb_convert_encoding($str,mb_detect_encoding($str),'UTF-8');
    }

    /*
    $str = str_split($str); <- this function is not mb safe, it splits by bytes, not characters. we cannot use it
    $str = preg_split('//u',$str); <- this function woulrd probably work fine but there was a bug reported in some php version so it pslits by bytes and not chars as well
    */
    preg_match_all('/.|\n/u', $str, $matches);
    $chars = $matches[0];
    $arabic_count = 0;
    $latin_count = 0;
    $total_count = 0;
    foreach($chars as $char) {
        //$pos = ord($char); we cant use that, its not binary safe
        $pos = uniord($char);

        if($pos >= 1536 && $pos <= 1791) {
            $arabic_count++;
        } else if($pos > 123 && $pos < 123) {
            $latin_count++;
        }
        $total_count++;
    }
    if(($arabic_count/$total_count) > 0.6) {
        // 60% arabic chars, its probably arabic
        return true;
    }
    return false;
}

function check_for_links($update, $MadelineProto)
{
    if (array_key_exists('message', $update['update'])) {
        if (array_key_exists('message', $update['update']['message'])) {
            $pattern = '~[a-z]+://\S+~';
            if (preg_match_all($pattern, $update['update']['message']['message'], $out)) return true;
            if (array_key_exists('entities', $update['update']['message'])) {
                foreach ($update['update']['message']['entities'] as $entity) {
                    if (isset($entity['_'])) {
                        $links = ["messageEntityUrl", "messageEntityTextUrl"];
                        if (in_array($entity['_'], $links)) return true;
                    }
                }
            }
            if (array_key_exists('media', $update['update']['message'])) {
                if (isset($update['update']['message']['media']['_'])) {
                    if ($update['update']['message']['media']['_'] == "messageMediaWebPage") return true;
                }
            }
        }
    }
    return false;
}
