<?php
// Enable error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$con = new mysqli('localhost', 'digita51_enock', 'digita51_enock', 'digita51_portal');

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

?>
