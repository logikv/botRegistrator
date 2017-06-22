<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 20.06.2017
 * Time: 14:42
 */

require_once '../sql-query/function.qb.php';
require_once "Mysql.php";


//$query = "INSERT INTO botresource (nickname, status, lastname, firstname) VALUES ('$profileNickname', '$profileStatus', '$profileLastName', '$profileFirstName')";
//$query = "INSERT INTO botresource (nickname, lastname, firstname) VALUES ('$profileNickname', '$profileLastName', '$profileFirstName')";


if ($_SERVER['REQUEST_METHOD'] == 'GET') {


    $result = qb()->table('profile_list')->where(array(
        'check' => 'null'
    ))->one();
    echo $result['nickname'];
    $delete = qb()->table('profile_list')->where(array('nickname' => $result['nickname']))->update(array('check' => 1));

}

$result = qb()->table('table_name')->where(array(
    'username' => 'testuser',
    'email' => 'email@site.com',
    'role' => array(1, 2, 3),
    array('status', '<>', 0)
))->all();


?>
