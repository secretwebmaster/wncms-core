<?php

use Wncms\Models\User;


if (!function_exists('wncms_get_users')) {
    function wncms_get_users()
    {
        if(isAdmin()){
            return User::all();
        }
        // return user manager
    }
}