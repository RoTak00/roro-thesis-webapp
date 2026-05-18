<?php

// display errors
ini_set('display_errors', $PHP_DISPLAY_ERRORS);
ini_set('display_startup_errors', $PHP_DISPLAY_STARTUP_ERRORS);
error_reporting($PHP_ERROR_REPORTING);

// default timezone
date_default_timezone_set($DEFAULT_TIMEZONE);

// Get Current date, time
$current_time = time();
$current_date = date("Y-m-d H:i:s", $current_time);

$ip = $_SERVER['REMOTE_ADDR'];


// Create database connection
$conn = new mysqli($SERVERNAME, $USERNAME, $PASSWORD, $DATABASE);


// Check connection
if ($conn->connect_error) {
    //AddAlert("Eroare la conectare la baza de date.", "danger");
    die("Connection failed: " . $conn->connect_error);
}

$conn->query('set character_set_client=utf8');
$conn->query('set character_set_connection=utf8');
$conn->query('set character_set_results=utf8');
$conn->query('set character_set_server=utf8');


?>