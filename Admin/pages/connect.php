<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'car_rental');

$con = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$con) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Service temporarily unavailable. Please try again later.");
}

mysqli_set_charset($con, 'utf8mb4');
?>