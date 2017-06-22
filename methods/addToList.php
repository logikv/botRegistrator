<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 19.06.2017
 * Time: 14:24
 */

require_once '../sql-query/function.qb.php';
require_once "Mysql.php";


$profileNickname = $_GET['profileNickname'];


//$query = "INSERT INTO botresource (nickname, status, lastname, firstname) VALUES ('$profileNickname', '$profileStatus', '$profileLastName', '$profileFirstName')";
//$query = "INSERT INTO botresource (nickname, lastname, firstname) VALUES ('$profileNickname', '$profileLastName', '$profileFirstName')";
//$query = "INSERT INTO profile_list (nickname) VALUE ('$profileNickname')";


if ($_SERVER['REQUEST_METHOD'] == 'GET') {


    if (isset($profileNickname) && $profileNickname !== "") {

        if ($result = qb()->table('profile_list')->insert(array('nickname' => $profileNickname))) {
            echo 'its work';
        } else {
            echo 'profile already exist';
        }

    } else {
        echo '$profileNickname не задан. $profileNickname является ключевым полем для далнейшего обращения';
    }
}


?>
