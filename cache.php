<?php

function cache_get_info($update, $MadelineProto, $data)
{
    $uMadelineProto = $MadelineProto->uMadelineProto;
    try {
        if (isset($MadelineProto->API->cached_data[$data])) {
            if ((time() - $MadelineProto->API->cached_data[$data]['date']) < 120) {
                $data_ = $MadelineProto->API->cached_data[$data]['data'];
            } else {
                $data_ =$uMadelineProto->get_info($data);
                $MadelineProto->API->cached_data[$data] = ['date' => time(), 'data' => $data_];
            }
        } else {
            $data_ =$uMadelineProto->get_info($data);
            $MadelineProto->API->cached_data[$data] = ['date' => time(), 'data' => $data_];
        }
        return($data_);
    } catch (Exception $e) {
        return false;
    }
}

function cache_get_chat_info($update, $MadelineProto, $full_fetch = false)
{
    $uMadelineProto = $MadelineProto->uMadelineProto;
        if (is_supergroup($update, $MadelineProto)) {
            $id = -100 . $update['update']['message']['to_id']['channel_id'];
            if (isset($MadelineProto->API->cached_full[$id])) {
                if ((time() - $MadelineProto->API->cached_full[$id]['date']) < 120) {
                    $info = $MadelineProto->API->cached_full[$id]['data'];
                } else {
                    try {
                        $info = $uMadelineProto->get_pwr_chat(-100 . $update['update']['message']['to_id']['channel_id']);
                        $MadelineProto->API->cached_full[$id] =
                        ['date' => time(), 'data' => $info];
                    } catch (Exception $e) {
                        return(false);
                    }
                }
            } else {
                $info = $uMadelineProto->get_pwr_chat(-100 . $update['update']['message']['to_id']['channel_id']);
                $MadelineProto->API->cached_full[$id]
                    = ['date' => time(), 'data' => $info];
            }
            return($info);
        }
}

function cache_from_user_info($update, $MadelineProto)
{
    $uMadelineProto = $MadelineProto->uMadelineProto;
    try {
        $id = $update['update']['message']['from_id'];
        if (isset($MadelineProto->API->cached_user[$id])) {
            if ((time() - $MadelineProto->API->cached_user[$id]['date']) < 120) {
                $user = $MadelineProto->API->cached_user[$id]['data'];
            } else {
                $user =$uMadelineProto->get_info($id);
                $MadelineProto->API->cached_user[$id] = ['date' => time(), 'data' => $user];
            }
        } else {
            $user =$uMadelineProto->get_info($id);
            $MadelineProto->API->cached_user[$id] = ['date' => time(), 'data' => $user];
        }
        return($user);
    } catch (Exception $e) {
        return array();
    }
}
