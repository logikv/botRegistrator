<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 22.06.2017
 * Time: 18:10
 */


$imgNum = 'bind';
$sid = $_GET['sid'];
$imgNum = $_GET['imgNum'];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {


    $result = qb()->table('botresource')->where(array(
        'check' => '1'
    ))->one();
    echo $result['nickname'] . '' . $result['lastname'] . '' . $result['firstname'];
    $bind = qb()->table('profile_list')->where(array('nickname' => $result['nickname']))->update(array($sid => $imgNum));


}