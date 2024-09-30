<?php

use Wncms\Models\Record;

if (!function_exists('wncms_add_record')) {
    function wncms_add_record($type, $sub_type, $status,  $message, $detail = null)
    {
        return Record::create([
            'type' => $type,
            'sub_type' => $sub_type,
            'status' => $status,
            'message' => $message,
            'detail' => $detail,
        ]);
    }
}

if (!function_exists('wncms_add_credit_record')) {
    function wncms_add_credit_record($type, $status, $amount, $remark = null)
    {
        return auth()->user()->credit_records()->create([
            'type' => $type,
            'status' => $status,
            'amount' => $amount,
            'remark' => $remark,
        ]);
    }
}
