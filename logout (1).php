<?php
session_start();
include 'connect.php';
$con->query("SET time_zone = '+03:00'");

if (isset($_SESSION['last_log_id'])) {
    $log_id = $_SESSION['last_log_id'];
    
    // Update the time AND set status to Logged Out
    mysqli_query($con, "UPDATE login_history 
                        SET last_activity = NOW(), 
                            session_status = 'Logged Out' 
                        WHERE id = '$log_id'");
}

session_destroy();
header("Location: index.php");
exit();