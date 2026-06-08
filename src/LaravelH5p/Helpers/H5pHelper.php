<?php

/*
 *
 * @Project        Expression project.displayName is undefined on line 5, column 35 in Templates/Licenses/license-default.txt.
 * @Copyright      Djoudi
 * @Created        2017-02-20
 * @Filename       H5pHelper.php
 * @Description
 *
 */

namespace Transisi\LaravelH5P\Helpers;

class H5pHelper
{
    //put your code here

    public static function current_user_can($permission)
    {
        return true;
    }

    public static function nonce($token)
    {
        return bin2hex($token);
    }
}
