<?php
/**
 * Created by PhpStorm.
 * User: bva
 * Date: 22.06.2017
 * Time: 18:10
 */

require_once '../sql-query/function.qb.php';


$sid = $_GET['sid'];
$imgNum = $_GET['imgNum'];

echo $sid.' '.$imgNum;


if ($_SERVER['REQUEST_METHOD'] == 'GET') {


    $result = qb()->table('botresource')->where(array(
        'ready' => '1'
    ))->one();
    echo $result['nickname'] . ' ' . $result['firstname'] . ' ' . $result['lastname'];
    $bind = qb()->table('profile_list')->where(array('nickname' => $result['nickname']))->update(array($sid => $imgNum));


} else {
    echo 'bad';

}

?>