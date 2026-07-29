<?php
session_start ();
include 'connect.php'; 

// Enable error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ⭐ CRITICAL STEP 1: Check if the user is logged in (ID is in session)
    if (!isset($_SESSION['id_no'])) {
        // You should likely redirect to a login page here, not just exit
        header('Location: index.php'); 
        exit;
    }

    // ⭐ CRITICAL STEP 2: Get the ID number ONLY from the secure session.
    $id_no = $_SESSION['id_no']; 

    // Get and sanitize primary beneficiary details with their statuses.
    $father = $_POST['father'] ?? '';
    $father_status = $_POST['father_status'] ?? 'Alive';
    $mother = $_POST['mother'] ?? '';
    $mother_status = $_POST['mother_status'] ?? 'Alive';
    $father_in_law = $_POST['father_in_law'] ?? '';
    $father_in_law_status = $_POST['father_in_law_status'] ?? 'Alive';
    $mother_in_law = $_POST['mother_in_law'] ?? '';
    $mother_in_law_status = $_POST['mother_in_law_status'] ?? 'Alive';
    $spouse = $_POST['spouse'] ?? '';
    $spouse_status = $_POST['spouse_status'] ?? 'Alive';

    // 1. PROCESS MAIN CHILDREN
    // The JS in the userdashboard.php sends children names as children[0], children[1], etc.
    // PHP receives this as $_POST['children'] which is an array of names.
    $all_children = [];
    if (isset($_POST['children']) && is_array($_POST['children'])) {
        foreach ($_POST['children'] as $name) {
            $name = trim($name);
            if (!empty($name)) {
                // Note: The form does NOT include a status field for children. Defaulting to 'Alive'.
                $all_children[] = [
                    'name' => $name,
                    'status' => 'Alive' // Default status since the field is missing in the form
                ];
            }
        }
    }
    
    // 2. PROCESS WIVES AND THEIR CHILDREN
    // The JS in the userdashboard.php sends wives as wives[0][name], wives[0][children][0], etc.
    // PHP receives this as $_POST['wives'], a multi-dimensional array.
    $all_wives = [];
    if (isset($_POST['wives']) && is_array($_POST['wives'])) {
        foreach ($_POST['wives'] as $wife_data) {
            $wife_name = trim($wife_data['name'] ?? '');
            $wife_status = $wife_data['status'] ?? 'Alive'; // Assuming status is included in the wife group
            
            if (!empty($wife_name)) {
                $wife_children = [];
                // Children for this wife are in wife_data['children']
                if (isset($wife_data['children']) && is_array($wife_data['children'])) {
                    foreach ($wife_data['children'] as $child_name) {
                        $child_name = trim($child_name);
                        if (!empty($child_name)) {
                            $wife_children[] = $child_name; 
                        }
                    }
                }
                
                $all_wives[] = [
                    'name' => $wife_name,
                    'status' => $wife_status,
                    'children' => $wife_children
                ];
            }
        }
    }

    // Convert the arrays to JSON strings for storage.
    // THIS IS WHERE THE CHILDREN DATA IS NOW CORRECTLY CONVERTED TO JSON.
    $children_json = json_encode($all_children);
    $wives_json = json_encode($all_wives);

    try {
        // First, check if a record already exists for this id_no.
        $check_stmt = $con->prepare("SELECT id_no FROM beneficiaries2 WHERE id_no = ?");
        $check_stmt->bind_param("s", $id_no);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        // The SQL query is updated to match the single-table JSON structure (children and wives fields).
        if ($check_result->num_rows > 0) {
            // Record exists, so UPDATE it.
            $sql = "UPDATE beneficiaries2 SET father = ?, mother = ?, spouse = ?, father_in_law = ?, mother_in_law = ?, children = ?, wives = ?, father_status = ?, mother_status = ?, spouse_status = ?, father_in_law_status = ?, mother_in_law_status = ? WHERE id_no = ?";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("sssssssssssss", 
                $father, $mother, $spouse, $father_in_law, $mother_in_law,
                $children_json, $wives_json,
                $father_status, $mother_status, $spouse_status,
                $father_in_law_status, $mother_in_law_status, $id_no
            );
        } else {
            // No record exists, so INSERT a new one.
            $sql = "INSERT INTO beneficiaries2 (id_no, father, mother, spouse, father_in_law, mother_in_law, children, wives, father_status, mother_status, spouse_status, father_in_law_status, mother_in_law_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("sssssssssssss", 
                $id_no, $father, $mother, $spouse, $father_in_law, $mother_in_law,
                $children_json, $wives_json,
                $father_status, $mother_status, $spouse_status,
                $father_in_law_status, $mother_in_law_status
            );
        }

        if ($stmt->execute()) {
            // Success: Redirect back to the user dashboard.
            // NOTE: Must close the check statement before redirecting.
            $check_stmt->close(); 
            $stmt->close();
            $con->close();
            header("Location: userdashboard.php");
            exit;
        } else {
            // Failure: Display an error message.
            echo "Error saving data: " . $stmt->error;
        }

        $stmt->close();
        $check_stmt->close();

    } catch (mysqli_sql_exception $e) {
        echo "Database Error: " . $e->getMessage();
    }
    
    $con->close();
} else {
    echo "Invalid request method.";
}
?>