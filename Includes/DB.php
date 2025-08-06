<?php
date_default_timezone_set('Africa/Cairo');

$DBservername = "localhost";
$DBusername = "root";
$DBpassword = "";
$DBName = "commune";

try {
  $pdo = new PDO("mysql:host=$DBservername;dbname=$DBName", $DBusername, $DBpassword, 
    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  //echo "Connected successfully";
} catch(PDOException $e) {
  
  echo "Connection failed: " . $e->getMessage();
}



?>