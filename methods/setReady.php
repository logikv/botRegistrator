<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 19.06.2017
 * Time: 12:07
 */

require_once '../sql-query/function.qb.php';
require_once "Mysql.php";


$profileNickname = $_GET['profileNickname'];


//$query = "UPDATE botresource  SET ready = 1 WHERE nickname LIKE '$profileNickname' LIMIT 1";


if ($_SERVER['REQUEST_METHOD'] == 'GET') {


    if (isset($profileNickname) && $profileNickname !== "") {

        if ($result = qb()->table('botresource')->where(array(
            'nickname' => $profileNickname
        ))->update(array('ready' => 1))
        )

            if (!$result = $mysqli->query("$query", MYSQLI_USE_RESULT)) {
                echo 'bad';
            }

    } else {
        echo '$profileNickname не задан. $profileNickname является ключевым полем для далнейшего обращения';
    }

}
