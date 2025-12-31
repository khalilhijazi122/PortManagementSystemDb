<?php
session_start();
$serverName = "WIRE777\SQLEXPRESS";
$database = "PortManagementSystemDb";

$connectionInfo = array(
    "Database" => $database,
    "Uid"=> "",
    "PWD"=> "",
    "CharacterSet" => "UTF-8"

);

$conn = sqlsrv_connect($serverName, $connectionInfo);
if ($conn === false) {
    die("Failed to connect with database " . print_r(sqlsrv_errors(), true));
}


function query($sql, $params = array()) {
    global $conn;
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        return false;
    }
    return $stmt;
}


function fetchAll($stmt) {
    $data = array();
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
    }
    return $data;
}


function fetchOne($stmt) {
    if ($stmt) {
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
    return null;
}

function formatDate($date) {
    if ($date instanceof DateTime) {
        return $date->format('Y-m-d');
    } else {
        return date('Y-m-d', strtotime($date));
    }
}


if (!isset($_SESSION['msg'])) {
    $_SESSION['msg'] = '';
    $_SESSION['msg_type'] = '';
}

function setMsg($msg, $type = 'success') {
    $_SESSION['msg'] = $msg;
    $_SESSION['msg_type'] = $type;
}

function showMsg() {
    if (!empty($_SESSION['msg'])) {
        $color = ($_SESSION['msg_type'] == 'success') ? '#43e97b' : '#fa709a';
        echo "<div style='padding:15px; margin:20px; background:{$color}; color:white; border-radius:10px; text-align:center;'>{$_SESSION['msg']}</div>";
        $_SESSION['msg'] = '';
    }
}
?>