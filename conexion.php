<?php
require_once __DIR__ . '/config.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
  die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>