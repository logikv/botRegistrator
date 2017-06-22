<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 16.06.2017
 * Time: 14:37
 */
require_once '../sql-query/function.qb.php';
require_once "Mysql.php";


$profileNickname = $_GET['profileNickname'];
$profileLastName = $_GET['profileLastName'];
$profileFirstName = $_GET['profileFirstName'];


//$query = "INSERT INTO botresource (nickname, status, lastname, firstname) VALUES ('$profileNickname', '$profileStatus', '$profileLastName', '$profileFirstName')";
$query = "INSERT INTO botresource (nickname, lastname, firstname) VALUES ('$profileNickname', '$profileLastName', '$profileFirstName')";


if ($_SERVER['REQUEST_METHOD'] == 'GET') {


    if (isset($profileNickname) && $profileNickname !== "") {

        if ($result = qb()->table('botresource')->insert(array(
            'nickname' => $profileNickname,
            'lastname' => $profileLastName,
            'firstname' => $profileFirstName
        ))
        ) {
            mkdir("/var/www/html/downloads/$profileNickname/", 1777);
            $addNewProfile = "success";
            echo $addNewProfile;
        }

        if ($result = $mysqli->query("$query", MYSQLI_USE_RESULT)) {

            mkdir("/var/www/html/downloads/$profileNickname/", 1777);
            $addNewProfile = "success";
            echo $addNewProfile;
            //          $result->close();
        } else {
            echo "profile already exist";
        }

    } else {
        echo '$profileNickname не задан. $profileNickname является ключевым полем для далнейшего обращения';
    }
}


?>