<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 16.06.2017
 * Time: 15:00
 */


function fwrite_stream($profileNickname, $imgName, $imgAlt)
{
    $name = fopen("/var/www/html/downloads/$profileNickname/$imgName.txt", "w");
    fwrite($name, "$imgAlt");
    fclose($name);
    return true;
}