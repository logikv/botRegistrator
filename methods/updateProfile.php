<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 16.06.2017
 * Time: 17:55
 */

require_once '../sql-query/function.qb.php';
require_once "func.inc.php";


$imgLink = $_GET['imgLink'];
$imgName = $_GET['imgName'];
$imgAlt = $_GET['imgAlt'];
$profileNickname = $_GET['profileNickname'];


//$query = "UPDATE botresource  SET count = $imgName  WHERE nickname LIKE '$profileNickname' LIMIT 1;";


if ($_SERVER['REQUEST_METHOD'] == 'GET') {


    if (isset($profileNickname) && $profileNickname !== "") {

        if (@copy($imgLink, "/var/www/html/downloads/$profileNickname/$imgName.jpg")) {
            $copyToPath = "success";


            if ($result = qb()->table('botresource')->where(array('nickname' => $profileNickname))->update(array('count' => $imgName))) {

                if ($fwrite = fwrite_stream($profileNickname, $imgName, $imgAlt)) {
                    $imgAltSave = "success";
                    echo 'All OK';
                } else {
                    echo "Возможно, в тексте параметра идет кавычка";
                }

            } else {
                echo 'не получилось сохранить файл';
            }

        } else {
            echo '$profileNickname не задан. $profileNickname является ключевым полем для далнейшего обращения';
        }
    }
}

?>