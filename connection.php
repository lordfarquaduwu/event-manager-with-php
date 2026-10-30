<?php
// Load optional config overrides
if(file_exists(__DIR__ . '/config.php')){
    include __DIR__ . '/config.php';
}

$servername = $DB_HOST ?? 'localhost';
$username = $DB_USER ?? 'root';
$password = $DB_PASS ?? '';
$dbname = $DB_NAME ?? 'event_management';
$dbport = $DB_PORT ?? 3307; // default to 3307 used by this project; change via config.php

$conn = new mysqli($servername, $username, $password, $dbname, $dbport);
if($conn->connect_error){
    die("Connection failed: ". $conn->connect_error);
}
echo "";
?>