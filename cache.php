<?php

function cache_get_info($update, $MadelineProto, $data)
{
    try {
        if (isset($MadelineProto->cached_data[$data])) {
            if ((time() - $MadelineProto->cached_data[$data]['date']) < 120) {
                $data_ = $MadelineProto->cached_data[$data]['data'];
            } else {
                $data_ =$MadelineProto->get_info($data);
                $MadelineProto->cached_data[$data] = ['date' => time(), 'data' => $data_];
            }
        } else {
            $data_ =$MadelineProto->get_info($data);
            $MadelineProto->cached_data[$data] = ['date' => time(), 'data' => $data_];
        }
        return($data_);
    } catch (Exception $e) {
        return false;
    }
}

function cache_get_chat_info($update, $MadelineProto, $full_fetch = false)
{
    try {
        if (is_supergroup($update, $MadelineProto)) {
            $id = -100 . $update['update']['message']['to_id']['channel_id'];
            if (isset($MadelineProto->cached_full[$id])) {
                if ((time() - $MadelineProto->cached_full[$id]['date']) < 120) {
                    $info = $MadelineProto->cached_full[$id]['data'];
                } else {
                    try {
                        $info = $MadelineProto->get_pwr_chat(-100 . $update['update']['message']['to_id']['channel_id']);
                        $MadelineProto->cached_full[$id] =
                        ['date' => time(), 'data' => $info];
                    } catch (Exception $e) {
                        return(false);
                    }
                }
            } else {
                $info = $MadelineProto->get_pwr_chat(-100 . $update['update']['message']['to_id']['channel_id']);
                $MadelineProto->cached_full[$id]
                    = ['date' => time(), 'data' => $info];
            }
            return($info);
        }
    } catch (Exception $e) {
        return array();
    }
}

function cache_from_user_info($update, $MadelineProto)
{
    $id = $update['update']['message']['from_id'];
    if (isset($MadelineProto->cached_user[$id])) {
        if ((time() - $MadelineProto->cached_user[$id]['date']) < 120) {
            $user = $MadelineProto->cached_user[$id]['data'];
        } else {
            $user =$MadelineProto->get_info($id);
            $MadelineProto->cached_user[$id] = ['date' => time(), 'data' => $user];
        }
    } else {
        $user =$MadelineProto->get_info($id);
        $MadelineProto->cached_user[$id] = ['date' => time(), 'data' => $user];
    }
    return($user);
}
