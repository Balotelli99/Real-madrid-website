<?php
$host = 'localhost';
$dbname = 'real_madrid';
$username = 'root';
$password = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli($host, $username, $password, $dbname);
$mysqli->set_charset('utf8mb4');
