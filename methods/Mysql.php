<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 16.06.2017
 * Time: 15:00
 */
$mysqli = new mysqli('192.168.0.66', 'botservice', 'Jledfyxbr', 'botservice');
if ($mysqli->connect_errno) {
    exit();
} else {
    $dbConnect = "success";
}
function response($copyToPath, $dbConnect, $imgAltSave, $addNewProfile = Null)
{
    echo json_encode(array(
            'copyToPath' => $copyToPath,
            'dbConnect' => $dbConnect,
            'imgAltSave' => $imgAltSave,
            'addNewProfile' => $addNewProfile)
    );
}

function fwrite_stream($profileNickname, $imgName, $imgAlt)
{
    $name = fopen("/var/www/html/downloads/$profileNickname/$imgName.txt", "w");
    fwrite($name, "$imgAlt");
    fclose($name);
    return true;
}